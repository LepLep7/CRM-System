<?php

namespace App\Services;

use App\Models\Pipeline;

class PipelineStageService
{
    public array $allowedTransitions = [
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

    public array $mainPath = ['new', 'qualify', 'proposal_submitted', 'shortlisted', 'verbal', 'contract', 'renewal'];

    public function isForwardTransition(string $from, string $to): bool
    {
        return in_array($to, $this->allowedTransitions[$from] ?? []);
    }

    /**
     * Checks the pipeline's CURRENTLY STORED (autosaved) attributes against
     * required fields for the target stage. Returns human-readable error messages.
     */
    public function missingFieldsForStage(Pipeline $pipeline, string $targetStage): array
    {
        $errors = [];

        // For Loss/Decline (exit branches), judge required fields by the
        // CURRENT stage (how far it progressed), not the exit stage itself.
        $referenceStage = in_array($targetStage, ['loss', 'decline']) ? $pipeline->stage : $targetStage;
        $referenceIndex = array_search($referenceStage, $this->mainPath);
        $reaches = fn (string $milestone) => $referenceIndex !== false
            && $referenceIndex >= array_search($milestone, $this->mainPath);

        if ($reaches('qualify')) {
            foreach ([
                'project_name' => 'Project name', 'chance_level' => 'Chance',
                'expected_start_date' => 'Expected start date', 'scope_of_service' => 'Scope of service',
                'customer_product' => 'Customer product',
            ] as $field => $label) {
                if (blank($pipeline->$field)) $errors[] = "$label is required.";
            }
        }

        if ($reaches('proposal_submitted')) {
            foreach ([
                'value_per_annum' => 'Value per annum', 'contract_period' => 'Contract period',
                'origin_country' => 'Origin country', 'port_of_loading' => 'Port of loading',
                'destination_country' => 'Destination country', 'port_of_destination' => 'Port of destination',
                'operating_profit_margin' => 'Operating profit margin',
            ] as $field => $label) {
                if (blank($pipeline->$field)) $errors[] = "$label is required.";
            }

            if ($pipeline->contract_period === 'adhoc'
                && ($pipeline->number_of_months < 1 || $pipeline->number_of_months > 11)) {
                $errors[] = 'AdHoc contracts must be between 1 and 11 months.';
            }

            if ($pipeline->attachments()->where('category', 'quotation')->count() === 0) {
                $errors[] = 'At least one quotation attachment is required.';
            }
        }

        if ($reaches('contract')) {
            foreach ([
                'date_secured' => 'Date secured', 'date_go_live' => 'Date go live',
            ] as $field => $label) {
                if (blank($pipeline->$field)) $errors[] = "$label is required.";
            }

            if ($pipeline->attachments()->where('category', 'contract')->count() === 0) {
                $errors[] = 'At least one contract attachment is required.';
            }
        }

        return $errors;
    }
}