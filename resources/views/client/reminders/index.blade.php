@extends('layouts.app')
@section('title', 'Reminders')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Reminders
        @if($unreadCount > 0)
            <span class="badge bg-primary">{{ $unreadCount }} unread</span>
        @endif
    </h1>
    @if($unreadCount > 0)
        <form method="POST" action="{{ route('client.reminders.mark-all-read') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-primary">Mark All as Read</button>
        </form>
    @endif
</div>

<div class="row">
    @forelse($reminders as $reminder)
        <div class="col-12 mb-3">
            <div class="card {{ !$reminder->is_read ? 'border-primary shadow-sm' : '' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="card-title d-flex align-items-center">
                                @if(!$reminder->is_read)
                                    <i class="fas fa-circle text-primary me-2" style="font-size: 8px;"></i>
                                @endif
                                {{ $reminder->title }}
                                @if(!$reminder->is_read)
                                    <span class="badge bg-primary ms-2">NEW</span>
                                @endif
                            </h6>
                            <p class="card-text">{{ $reminder->message }}</p>
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> {{ $reminder->created_at->diffForHumans() }}
                                @if($reminder->due_date)
                                    | <i class="fas fa-calendar"></i> Due: {{ $reminder->due_date->format('M d, Y') }}
                                @endif
                            </small>
                        </div>
                        <div class="ms-3 d-flex flex-column align-items-end">
                            <span class="badge {{ $reminder->getTypeBadgeClass() }} mb-2">
                                {{ $reminder->getTypeDisplayName() }}
                            </span>
                            @if(!$reminder->is_read)
                                <button class="btn btn-sm btn-outline-secondary mark-read"
                                        data-id="{{ $reminder->id }}">
                                    Mark Read
                                </button>
                            @endif
                        </div>
                    </div>
                    @if($reminder->pbcRequest)
                        <div class="mt-3 pt-2 border-top">
                            <a href="{{ route('client.pbc-requests.show', $reminder->pbcRequest) }}"
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View PBC Request
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                <h5>No reminders</h5>
                <p class="text-muted">You don't have any reminders at the moment.</p>
                <a href="{{ route('client.pbc-requests.index') }}" class="btn btn-primary">
                    View PBC Requests
                </a>
            </div>
        </div>
    @endforelse
</div>

@if($reminders->count() > 0)
    <div class="mt-4 text-center">
        <small class="text-muted">
            Showing {{ $reminders->count() }} most recent reminders
        </small>
    </div>
@endif
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark individual reminder as read
    document.querySelectorAll('.mark-read').forEach(button => {
        button.addEventListener('click', function() {
            const reminderId = this.dataset.id;
            const button = this;

            fetch(`/client/reminders/${reminderId}/mark-read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove visual indicators
                    const card = button.closest('.card');
                    card.classList.remove('border-primary', 'shadow-sm');

                    // Remove new badge and mark read button
                    const title = card.querySelector('.card-title');
                    const newBadge = title.querySelector('.badge.bg-primary');
                    const circle = title.querySelector('.fas.fa-circle');

                    if (newBadge) newBadge.remove();
                    if (circle) circle.remove();
                    button.remove();

                    // Update unread count in header
                    const headerBadge = document.querySelector('h1 .badge.bg-primary');
                    if (headerBadge) {
                        let count = parseInt(headerBadge.textContent.split(' ')[0]) - 1;
                        if (count <= 0) {
                            headerBadge.remove();
                            const markAllBtn = document.querySelector('.btn.btn-outline-primary');
                            if (markAllBtn) markAllBtn.remove();
                        } else {
                            headerBadge.textContent = `${count} unread`;
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error marking reminder as read:', error);
            });
        });
    });
});
</script>
@endsection
