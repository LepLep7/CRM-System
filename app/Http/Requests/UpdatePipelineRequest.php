<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePipelineRequest extends FormRequest
{
    /**
     * Valid forward transitions from each stage.
     * Anything not listed here for the current stage is treated as a rollback.
     */
    protected array $allowedTransitions = [
        'new' => ['qualify'],
        'qualify' => ['proposal_submitted', 'loss'],
        'proposal_submitted' => ['shortlisted', 'loss'],
        'shortlisted' => ['verbal', 'loss'],
        'verbal' => ['contract', 'loss'],
        'contract' => ['renewal', 'decline'],
        'renewal' => [],
        'loss' => [],
        'decline' => [],
    ];

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('pipeline'));
    }

    public function rules(): array
    {
        $pipeline = $this->route('pipeline');
        $targetStage = $this->input('stage', $pipeline->stage);

        $rules = [
            'stage' => ['required', 'in:' . implode(',', array_keys($this->allowedTransitions))],

            // Qualify fields
            'project_name' => 'nullable|string|max:255',
            'chance_percent' => 'nullable|integer|min:0|max:100',
            'expected_start_date' => 'nullable|date|after_or_equal:today',
            'scope_of_service' => 'nullable|string',
            'customer_product' => 'nullable|string|max:255',

            // Proposal / Shortlisted / Verbal fields
            'value_per_annum' => 'nullable|numeric|min:0',
            'contract_period' => 'nullable|in:long_term,adhoc',
            'number_of_months' => 'nullable|integer|min:1|max:12',
            'origin_country' => 'nullable|string|max:255',
            'port_of_loading' => 'nullable|string|max:255',
            'destination_country' => 'nullable|string|max:255',
            'port_of_destination' => 'nullable|string|max:255',
            'operating_profit_margin' => 'nullable|numeric|min:0|max:100',
            'remarks_proposal' => 'nullable|string',

            // Contract / Renewal / Decline fields
            'date_secured' => 'nullable|date|after_or_equal:today',
            'date_go_live' => 'nullable|date|after_or_equal:today',
            'remarks_contract' => 'nullable|string',

            // Attachments — validated for file type/size here; "at least 1" checked in withValidator
            // because it must account for files already stored from a previous stage, not just this request.
            'quotation_attachments' => 'nullable|array',
            'quotation_attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
            'contract_attachments' => 'nullable|array',
            'contract_attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ];

        // Reaching Qualify (or any later stage) makes these required
        if ($this->reaches('qualify', $targetStage)) {
            $rules['project_name'] = 'required|string|max:255';
            $rules['chance_percent'] = 'required|integer|min:0|max:100';
            $rules['expected_start_date'] = 'required|date|after_or_equal:today';
            $rules['scope_of_service'] = 'required|string';
            $rules['customer_product'] = 'required|string|max:255';
        }

        // Reaching Proposal Submitted / Shortlisted / Verbal (or Contract/Renewal/Decline after)
        if ($this->reaches('proposal_submitted', $targetStage)) {
            $rules['value_per_annum'] = 'required|numeric|min:0';
            $rules['contract_period'] = 'required|in:long_term,adhoc';
            $rules['origin_country'] = 'required|string|max:255';
            $rules['port_of_loading'] = 'required|string|max:255';
            $rules['destination_country'] = 'required|string|max:255';
            $rules['port_of_destination'] = 'required|string|max:255';
            $rules['operating_profit_margin'] = 'required|numeric|min:0|max:100';
        }

        // Reaching Contract / Renewal / Decline
        if ($this->reaches('contract', $targetStage)) {
            $rules['date_secured'] = 'required|date|after_or_equal:today';
            $rules['date_go_live'] = 'required|date|after_or_equal:today';
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $pipeline = $this->route('pipeline');
            $targetStage = $this->input('stage');

            // Rollback check: forward moves must match the transition map
            if (! $this->isForwardTransition($pipeline->stage, $targetStage)
                && $targetStage !== $pipeline->stage
                && ! in_array($this->user()->role, ['manager', 'admin'])) {
                $validator->errors()->add(
                    'stage',
                    'Only a Manager or Admin can move a pipeline back to a previous stage.'
                );
            }

            // AdHoc contract period must be 1–11 months
            if ($this->input('contract_period') === 'adhoc') {
                $months = (int) $this->input('number_of_months');
                if ($months < 1 || $months > 11) {
                    $validator->errors()->add(
                        'number_of_months',
                        'AdHoc contracts must be between 1 and 11 months.'
                    );
                }
            }

            // Quotation attachments: at least 1 total (existing in DB + newly uploaded now)
            if ($this->reaches('proposal_submitted', $targetStage)) {
                $existing = $pipeline->attachments()->where('category', 'quotation')->count();
                $incoming = count($this->file('quotation_attachments') ?? []);
                if ($existing + $incoming < 1) {
                    $validator->errors()->add(
                        'quotation_attachments',
                        'At least one quotation attachment is required.'
                    );
                }
            }

            // Contract attachments: at least 1 total
            if ($this->reaches('contract', $targetStage)) {
                $existing = $pipeline->attachments()->where('category', 'contract')->count();
                $incoming = count($this->file('contract_attachments') ?? []);
                if ($existing + $incoming < 1) {
                    $validator->errors()->add(
                        'contract_attachments',
                        'At least one contract attachment is required.'
                    );
                }
            }
        });
    }

    /**
     * Whether moving from $from to $to is a valid forward transition per the business rules.
     */
    public function isForwardTransition(string $from, string $to): bool
    {
        return in_array($to, $this->allowedTransitions[$from] ?? []);
    }

    public function getAllowedTransitions(): array
    {
        return $this->allowedTransitions;
    }

    /**
     * Whether the reference point (current stage, when exiting via Loss/Decline;
     * otherwise the target stage) reaches or passes a given milestone stage.
     */
    protected function reaches(string $milestone, ?string $targetStage): bool
    {
        if (! $targetStage) {
            return false;
        }

        $mainPath = ['new', 'qualify', 'proposal_submitted', 'shortlisted', 'verbal', 'contract', 'renewal'];
        $milestoneIndex = array_search($milestone, $mainPath);

        // Loss/Decline are exit branches: how far the pipeline progressed is judged
        // by the CURRENT stage (where it's exiting from), not the exit stage itself.
        if (in_array($targetStage, ['loss', 'decline'])) {
            $pipeline = $this->route('pipeline');
            $referenceIndex = array_search($pipeline->stage, $mainPath);

            return $referenceIndex !== false && $referenceIndex >= $milestoneIndex;
        }

        $targetIndex = array_search($targetStage, $mainPath);

        return $targetIndex !== false && $targetIndex >= $milestoneIndex;
    }
}