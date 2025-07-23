@extends('layouts.app')
@section('title', 'Create PBC Request')

@section('content')
<h1>Create New PBC Request</h1>

<form method="POST" action="{{ route('admin.pbc-requests.store') }}">
    @csrf
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Request Title</label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}" required placeholder="e.g., Year-End Audit 2024">
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                          rows="3" placeholder="Optional description of the request">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Select Client</label>
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
                <label class="form-label">Select Project</label>
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
                <label class="form-label">Use Template (Optional)</label>
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
                <label class="form-label">Due Date</label>
                <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror"
                       value="{{ old('due_date') }}" min="{{ date('Y-m-d') }}">
                @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Engagement Partner</label>
                <input type="text" name="header_info[engagement_partner]" class="form-control"
                       value="{{ old('header_info.engagement_partner') }}" placeholder="Partner name">
            </div>

            <div class="mb-3">
                <label class="form-label">Engagement Manager</label>
                <input type="text" name="header_info[engagement_manager]" class="form-control"
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
                            <label class="form-label">Document Required (Particulars)</label>
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
                        <label class="form-label">Document Required (Particulars)</label>
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
let itemIndex = {{ old('items') ? count(old('items')) : 1 }};

// Add new item
document.getElementById('add-item').addEventListener('click', function() {
    const container = document.getElementById('request-items');
    const div = document.createElement('div');
    div.className = 'request-item mb-3 p-3 border';
    div.innerHTML = `
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <input type="text" name="items[${itemIndex}][category]" class="form-control" placeholder="e.g., Financial, Legal">
            </div>
            <div class="col-md-5">
                <label class="form-label">Document Required (Particulars)</label>
                <textarea name="items[${itemIndex}][particulars]" class="form-control" rows="2" required placeholder="Describe the document needed"></textarea>
            </div>
            <div class="col-md-2">
                <label class="form-label">Date Requested</label>
                <input type="date" name="items[${itemIndex}][date_requested]" class="form-control" value="${new Date().toISOString().split('T')[0]}">
            </div>
            <div class="col-md-1">
                <label class="form-label">Required?</label>
                <select name="items[${itemIndex}][is_required]" class="form-control">
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
                <input type="text" name="items[${itemIndex}][remarks]" class="form-control" placeholder="Additional notes or instructions">
            </div>
        </div>
    `;
    container.appendChild(div);
    itemIndex++;
});

// Remove item
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-item')) {
        // Don't allow removing the last item
        const items = document.querySelectorAll('.request-item');
        if (items.length > 1) {
            e.target.closest('.request-item').remove();
        } else {
            alert('At least one item is required.');
        }
    }
});

// Load template items when template is selected
document.getElementById('template-select').addEventListener('change', function() {
    const templateId = this.value;
    if (templateId) {
        fetch(`/admin/pbc-templates/${templateId}/items`)
            .then(response => response.json())
            .then(items => {
                // Clear existing items except the first one
                const container = document.getElementById('request-items');
                container.innerHTML = '';

                // Add template items
                items.forEach((item, index) => {
                    const div = document.createElement('div');
                    div.className = 'request-item mb-3 p-3 border';
                    div.innerHTML = `
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Category</label>
                                <input type="text" name="items[${index}][category]" class="form-control" value="${item.category || ''}" placeholder="e.g., Financial, Legal">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Document Required (Particulars)</label>
                                <textarea name="items[${index}][particulars]" class="form-control" rows="2" required>${item.particulars}</textarea>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Date Requested</label>
                                <input type="date" name="items[${index}][date_requested]" class="form-control" value="${new Date().toISOString().split('T')[0]}">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Required?</label>
                                <select name="items[${index}][is_required]" class="form-control">
                                    <option value="1" ${item.is_required ? 'selected' : ''}>Yes</option>
                                    <option value="0" ${!item.is_required ? 'selected' : ''}>No</option>
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
                                <input type="text" name="items[${index}][remarks]" class="form-control" placeholder="Additional notes or instructions">
                            </div>
                        </div>
                    `;
                    container.appendChild(div);
                });

                itemIndex = items.length;
            })
            .catch(error => {
                console.error('Error loading template items:', error);
                alert('Error loading template items. Please try again.');
            });
    }
});
</script>
@endsection
