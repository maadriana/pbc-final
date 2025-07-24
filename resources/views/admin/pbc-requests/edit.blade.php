@extends('layouts.app')
@section('title', 'Edit PBC Request')

@section('content')
<h1>Edit PBC Request</h1>

@if($pbcRequest->sent_at)
    <div class="alert alert-warning">
        <strong>Note:</strong> This request has already been sent to the client on {{ $pbcRequest->sent_at->format('M d, Y H:i') }}.
        Editing will not affect items that have already been uploaded by the client.
    </div>
@endif

<form method="POST" action="{{ route('admin.pbc-requests.update', $pbcRequest) }}">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Request Title</label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title', $pbcRequest->title) }}" required>
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                          rows="3">{{ old('description', $pbcRequest->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Client</label>
                <select name="client_id" class="form-control @error('client_id') is-invalid @enderror" required>
                    @foreach(\App\Models\Client::with('user')->orderBy('company_name')->get() as $client)
                        <option value="{{ $client->id }}" {{ old('client_id', $pbcRequest->client_id) == $client->id ? 'selected' : '' }}>
                            {{ $client->company_name }} ({{ $client->user->email }})
                        </option>
                    @endforeach
                </select>
                @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Project</label>
                <select name="project_id" class="form-control @error('project_id') is-invalid @enderror" required>
                    @foreach(\App\Models\Project::where('status', 'active')->orderBy('name')->get() as $project)
                        <option value="{{ $project->id }}" {{ old('project_id', $pbcRequest->project_id) == $project->id ? 'selected' : '' }}>
                            {{ $project->name }}
                        </option>
                    @endforeach
                </select>
                @error('project_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Due Date</label>
                <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror"
                       value="{{ old('due_date', $pbcRequest->due_date ? $pbcRequest->due_date->format('Y-m-d') : '') }}">
                @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Engagement Partner</label>
                <input type="text" name="header_info[engagement_partner]" class="form-control"
                       value="{{ old('header_info.engagement_partner', $pbcRequest->header_info['engagement_partner'] ?? '') }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Engagement Manager</label>
                <input type="text" name="header_info[engagement_manager]" class="form-control"
                       value="{{ old('header_info.engagement_manager', $pbcRequest->header_info['engagement_manager'] ?? '') }}">
            </div>
        </div>
    </div>

    <hr>

    <h3>Request Items</h3>
    <div id="request-items">
        @foreach($pbcRequest->items as $index => $item)
            <div class="request-item mb-3 p-3 border {{ $item->documents->count() > 0 ? 'bg-light' : '' }}">
                @if($item->documents->count() > 0)
                    <div class="alert alert-info alert-sm">
                        <small><strong>Note:</strong> This item has {{ $item->documents->count() }} uploaded file(s). Changes may affect client submissions.</small>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <input type="text" name="items[{{ $index }}][category]" class="form-control"
                               value="{{ old('items.'.$index.'.category', $item->category) }}">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Document Required (Particulars)</label>
                        <textarea name="items[{{ $index }}][particulars]" class="form-control" rows="2" required>{{ old('items.'.$index.'.particulars', $item->particulars) }}</textarea>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date Requested</label>
                        <input type="date" name="items[{{ $index }}][date_requested]" class="form-control"
                               value="{{ old('items.'.$index.'.date_requested', $item->date_requested ? $item->date_requested->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Required?</label>
                        <select name="items[{{ $index }}][is_required]" class="form-control">
                            <option value="1" {{ old('items.'.$index.'.is_required', $item->is_required) ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ !old('items.'.$index.'.is_required', $item->is_required) ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label><br>
                        <button type="button" class="btn btn-sm btn-danger remove-item" {{ $item->documents->count() > 0 ? 'disabled' : '' }}>Remove</button>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-12">
                        <label class="form-label">Remarks (Optional)</label>
                        <input type="text" name="items[{{ $index }}][remarks]" class="form-control"
                               value="{{ old('items.'.$index.'.remarks', $item->remarks) }}">
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if(!$pbcRequest->sent_at)
        <button type="button" class="btn btn-secondary mb-3" id="add-item">Add Another Item</button>
    @endif

    <hr>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Update PBC Request</button>
        <a href="{{ route('admin.pbc-requests.show', $pbcRequest) }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection

@section('scripts')
<script>
let itemIndex = {{ $pbcRequest->items->count() }};

// Add new item (only if not sent)
@if(!$pbcRequest->sent_at)
document.getElementById('add-item')?.addEventListener('click', function() {
    const container = document.getElementById('request-items');
    const div = document.createElement('div');
    div.className = 'request-item mb-3 p-3 border';
    div.innerHTML = `
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <input type="text" name="items[${itemIndex}][category]" class="form-control">
            </div>
            <div class="col-md-5">
                <label class="form-label">Document Required (Particulars)</label>
                <textarea name="items[${itemIndex}][particulars]" class="form-control" rows="2" required></textarea>
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
                <input type="text" name="items[${itemIndex}][remarks]" class="form-control">
            </div>
        </div>
    `;
    container.appendChild(div);
    itemIndex++;
});
@endif

// Remove item
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-item') && !e.target.disabled) {
        const items = document.querySelectorAll('.request-item');
        if (items.length > 1) {
            e.target.closest('.request-item').remove();
        } else {
            alert('At least one item is required.');
        }
    }
});
</script>
@endsection
