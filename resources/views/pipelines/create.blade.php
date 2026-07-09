<x-app-layout>
    <x-slot name="header">
        <h2 class="fs-4 fw-semibold">New pipeline</h2>
    </x-slot>

    <div class="container py-4">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

        <form method="POST" action="{{ route('pipelines.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Existing customer</label>
                <select name="customer_id" id="customer_id" class="form-select">
                    <option value="">-- Select existing customer --</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>

            <p class="text-muted">Or fill in a new customer:</p>

            <div id="new-customer-fields">
                <div class="mb-3">
                    <label class="form-label">New customer name</label>
                    <input type="text" name="new_customer_name" id="new_customer_name" class="form-control" value="{{ old('new_customer_name') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">New customer email</label>
                    <input type="email" name="new_customer_email" id="new_customer_email" class="form-control" value="{{ old('new_customer_email') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">New customer phone</label>
                    <input type="text" name="new_customer_phone" id="new_customer_phone" class="form-control" value="{{ old('new_customer_phone') }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Project name</label>
                <input type="text" name="project_name" class="form-control" value="{{ old('project_name') }}" required>
            </div>

            @error('new_customer_name')
                <div class="text-danger mb-2">{{ $message }}</div>
            @enderror

            <button type="submit" class="btn btn-primary">Create pipeline</button>
        </form>
    </div>

    <script>
        const customerSelect = document.getElementById('customer_id');
        const newCustomerFields = document.getElementById('new-customer-fields');
        const newCustomerInputs = newCustomerFields.querySelectorAll('input');

        function toggleNewCustomerFields() {
            const hasExisting = customerSelect.value !== '';

            newCustomerInputs.forEach(input => {
                input.disabled = hasExisting;
                if (hasExisting) input.value = '';
            });

            newCustomerFields.style.opacity = hasExisting ? 0.5 : 1;
        }

        function toggleCustomerSelect() {
            const hasNewCustomerInput = Array.from(newCustomerInputs).some(input => input.value.trim() !== '');
            customerSelect.disabled = hasNewCustomerInput;
            customerSelect.parentElement.style.opacity = hasNewCustomerInput ? 0.5 : 1;
        }

        customerSelect.addEventListener('change', toggleNewCustomerFields);
        newCustomerInputs.forEach(input => input.addEventListener('input', toggleCustomerSelect));
    </script>
</x-app-layout>