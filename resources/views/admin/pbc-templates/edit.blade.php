@extends('layouts.app')
@section('title', 'Edit PBC Template')

@section('content')
<h1>Edit PBC Template</h1>

<form method="POST" action="{{ route('admin.pbc-templates.update', $pbcTemplate) }}">
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="form-label">Template Name</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $pbcTemplate->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $pbcTemplate->description) }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" value="1"
                           {{ old('is_active', $pbcTemplate->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label">Active Template</label>
                </div>
            </div>
        </div>
    </div>

    <h3>Template Items</h3>
    <div id="template-items">
        @foreach($pbcTemplate->templateItems as $index => $item)
            <div class="template-item mb-3 p-3 border">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <input type="text" name="items[{{ $index }}][category]" class="form-control"
                               value="{{ old('items.'.$index.'.category', $item->category) }}" placeholder="e.g., Financial, Legal">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Particulars (Required Document)</label>
                        <textarea name="items[{{ $index }}][particulars]" class="form-control" rows="2" required>{{ old('items.'.$index.'.particulars', $item->particulars) }}</textarea>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Required?</label>
                        <select name="items[{{ $index }}][is_required]" class="form-control">
                            <option value="1" {{ old('items.'.$index.'.is_required', $item->is_required) ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ !old('items.'.$index.'.is_required', $item->is_required) ? 'selected' : '' }}>No</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label><br>
                        <button type="button" class="btn btn-sm btn-danger remove-item">Remove</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <button type="button" class="btn btn-secondary mb-3" id="add-item">Add Item</button>

    <div>
        <button type="submit" class="btn btn-primary">Update Template</button>
        <a href="{{ route('admin.pbc-templates.show', $pbcTemplate) }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection

@section('scripts')
<script>
let itemIndex = {{ $pbcTemplate->templateItems->count() }};

document.getElementById('add-item').addEventListener('click', function() {
    const container = document.getElementById('template-items');
    const div = document.createElement('div');
    div.className = 'template-item mb-3 p-3 border';
    div.innerHTML = `
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <input type="text" name="items[${itemIndex}][category]" class="form-control" placeholder="e.g., Financial, Legal">
            </div>
            <div class="col-md-6">
                <label class="form-label">Particulars (Required Document)</label>
                <textarea name="items[${itemIndex}][particulars]" class="form-control" rows="2" required></textarea>
            </div>
            <div class="col-md-2">
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
    `;
    container.appendChild(div);
    itemIndex++;
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-item')) {
        e.target.closest('.template-item').remove();
    }
});
</script>
@endsection
