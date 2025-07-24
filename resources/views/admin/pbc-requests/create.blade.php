@extends('layouts.app')
@section('title', 'Create PBC Request')

@section('content')
<h1>Create New PBC Request</h1>

<form method="POST" action="{{ route('admin.pbc-requests.store') }}" id="pbc-form">
    @csrf
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="title" class="form-label">Request Title</label>
                <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}" required placeholder="e.g., Year-End Audit 2024">
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror"
                          rows="3" placeholder="Optional description of the request">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="client-select" class="form-label">Select Client</label>
                <select name="client_id" class="form-control @error('client_id') is-invalid @enderror" required id="client-select">
                    <option value="">Choose Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->company_name }} ({{ $client->user->email }})
                        </option>
                    @endforeach
                </select>
                @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="project-select" class="form-label">Select Project</label>
                <select name="project_id" class="form-control @error('project_id') is-invalid @enderror" required id="project-select">
                    <option value="">Choose Project</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
                @error('project_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="template-select" class="form-label">Use Template (Optional)</label>
                <select name="template_id" class="form-control" id="template-select">
                    <option value="">Create from scratch</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}" {{ old('template_id') == $template->id ? 'selected' : '' }}>
                            {{ $template->name }} ({{ $template->templateItems->count() }} items)
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Select a template to auto-populate request items</small>
            </div>

            <div class="mb-3">
                <label for="due-date" class="form-label">Due Date</label>
                <input type="date" id="due-date" name="due_date" class="form-control @error('due_date') is-invalid @enderror"
                       value="{{ old('due_date') }}" min="{{ date('Y-m-d') }}">
                @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="engagement-partner" class="form-label">Engagement Partner</label>
                <input type="text" id="engagement-partner" name="header_info[engagement_partner]" class="form-control"
                       value="{{ old('header_info.engagement_partner') }}" placeholder="Partner name">
            </div>

            <div class="mb-3">
                <label for="engagement-manager" class="form-label">Engagement Manager</label>
                <input type="text" id="engagement-manager" name="header_info[engagement_manager]" class="form-control"
                       value="{{ old('header_info.engagement_manager') }}" placeholder="Manager name">
            </div>
        </div>
    </div>

    <hr>

    <h3>Request Items</h3>
    <p class="text-muted">Add the documents you need from the client. You can use a template above to auto-populate items.</p>

    <div id="request-items">
        @if(old('items'))
            @foreach(old('items') as $index => $item)
                <div class="request-item mb-3 p-3 border">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="items[{{ $index }}][category]" class="form-control"
                                   value="{{ $item['category'] ?? '' }}" placeholder="e.g., Financial, Legal">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Document Required (Particulars) <span class="text-danger">*</span></label>
                            <textarea name="items[{{ $index }}][particulars]" class="form-control" rows="2" required>{{ $item['particulars'] ?? '' }}</textarea>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date Requested</label>
                            <input type="date" name="items[{{ $index }}][date_requested]" class="form-control"
                                   value="{{ $item['date_requested'] ?? date('Y-m-d') }}">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">Required?</label>
                            <select name="items[{{ $index }}][is_required]" class="form-control">
                                <option value="1" {{ ($item['is_required'] ?? true) ? 'selected' : '' }}>Yes</option>
                                <option value="0" {{ !($item['is_required'] ?? true) ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label><br>
                            <button type="button" class="btn btn-sm btn-danger remove-item">Remove</button>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <label class="form-label">Remarks (Optional)</label>
                            <input type="text" name="items[{{ $index }}][remarks]" class="form-control"
                                   value="{{ $item['remarks'] ?? '' }}" placeholder="Additional notes or instructions">
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <!-- Default empty item -->
            <div class="request-item mb-3 p-3 border">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <input type="text" name="items[0][category]" class="form-control" placeholder="e.g., Financial, Legal">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Document Required (Particulars) <span class="text-danger">*</span></label>
                        <textarea name="items[0][particulars]" class="form-control" rows="2" required placeholder="Describe the document needed"></textarea>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date Requested</label>
                        <input type="date" name="items[0][date_requested]" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Required?</label>
                        <select name="items[0][is_required]" class="form-control">
                            <option value="1" selected>Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label><br>
                        <button type="button" class="btn btn-sm btn-danger remove-item">Remove</button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <label class="form-label">Remarks (Optional)</label>
                        <input type="text" name="items[0][remarks]" class="form-control" placeholder="Additional notes or instructions">
                    </div>
                </div>
            </div>
        @endif
    </div>

    <button type="button" class="btn btn-secondary mb-3" id="add-item">Add Another Item</button>

    <hr>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Create PBC Request</button>
        <button type="submit" name="send_immediately" value="1" class="btn btn-success">Create & Send to Client</button>
        <a href="{{ route('admin.pbc-requests.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection

@section('scripts')
<script>
// Enhanced debugging version
document.getElementById('template-select').addEventListener('change', function() {
    const templateId = this.value;
    const selectedOption = this.options[this.selectedIndex];
    const templateName = selectedOption.text;

    console.log('=== TEMPLATE SELECTION DEBUG ===');
    console.log('Selected template ID:', templateId);
    console.log('Selected template name:', templateName);
    console.log('Selected option:', selectedOption);

    if (!templateId) {
        console.log('No template selected, exiting');
        return;
    }

    const url = `/admin/pbc-templates/${templateId}/items`;
    console.log('Fetching from URL:', url);

    fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.json();
        })
        .then(result => {
            console.log('=== SERVER RESPONSE ===');
            console.log('Full response:', result);

            if (result.debug_info) {
                console.log('Server debug info:', result.debug_info);
                console.log('Server thinks template ID is:', result.debug_info.template_id);
                console.log('Server thinks template name is:', result.debug_info.template_name);
            }

            const items = result.data || result;

            if (!Array.isArray(items) || items.length === 0) {
                console.log('No items found. Full result:', result);
                alert(`No items found for template "${templateName}" (ID: ${templateId}). Check console for details.`);
                return;
            }

            console.log(`Found ${items.length} items:`, items);

            // Clear and populate form
            const container = document.getElementById('request-items');
            container.innerHTML = '';

            items.forEach((item, index) => {
                console.log(`Adding item ${index}:`, item);
                const div = document.createElement('div');
                div.innerHTML = createItemHTML(index, item);
                container.appendChild(div.firstElementChild);
            });

            itemIndex = items.length;
            console.log('=== SUCCESS ===');
            console.log('Template loaded successfully. Total items:', items.length);
        })
        .catch(error => {
            console.error('=== ERROR ===');
            console.error('Fetch error:', error);
            alert('Error loading template: ' + error.message + '. Check console for details.');
        });
});
</script>

@endsection
