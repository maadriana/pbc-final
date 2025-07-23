@extends('layouts.app')
@section('title', 'Template Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>{{ $pbcTemplate->name }}</h1>
    <div>
        <a href="{{ route('admin.pbc-templates.edit', $pbcTemplate) }}" class="btn btn-warning">Edit Template</a>
        <a href="{{ route('admin.pbc-templates.index') }}" class="btn btn-secondary">Back to Templates</a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5>Template Items</h5></div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr><th>#</th><th>Category</th><th>Particulars</th><th>Required</th></tr>
                    </thead>
                    <tbody>
                        @forelse($pbcTemplate->templateItems as $item)
                        <tr>
                            <td>{{ $item->order_index + 1 }}</td>
                            <td>{{ $item->category ?? 'General' }}</td>
                            <td>{{ $item->particulars }}</td>
                            <td>
                                <span class="badge bg-{{ $item->is_required ? 'success' : 'secondary' }}">
                                    {{ $item->is_required ? 'Yes' : 'No' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center">No items found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h5>Template Info</h5></div>
            <div class="card-body">
                <p><strong>Description:</strong> {{ $pbcTemplate->description ?? 'N/A' }}</p>
                <p><strong>Status:</strong>
                    <span class="badge bg-{{ $pbcTemplate->is_active ? 'success' : 'secondary' }}">
                        {{ $pbcTemplate->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </p>
                <p><strong>Total Items:</strong> {{ $pbcTemplate->templateItems->count() }}</p>
                <p><strong>Required Items:</strong> {{ $pbcTemplate->templateItems->where('is_required', true)->count() }}</p>
                <p><strong>Created By:</strong> {{ $pbcTemplate->creator->name }}</p>
                <p><strong>Created:</strong> {{ $pbcTemplate->created_at->format('M d, Y') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
