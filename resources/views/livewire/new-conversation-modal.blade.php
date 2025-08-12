<div>
    <div wire:ignore.self class="modal fade" id="newConversationModal" tabindex="-1" role="dialog" aria-labelledby="newConversationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newConversationModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Start New Conversation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="mount"></button>
                </div>
                <div class="modal-body">
                    <!-- Search Contacts -->
                    <div class="mb-3">
                        <label for="contactSearch" class="form-label">Search Contacts</label>
                        <input type="text"
                               id="contactSearch"
                               wire:model.live="searchTerm"
                               placeholder="Search by name or email..."
                               class="form-control">
                    </div>

                    <!-- Contact List -->
                    <div class="contact-list" style="max-height: 300px; overflow-y: auto;">
                        @forelse($this->availableContacts as $contact)
                            <div class="contact-item d-flex align-items-center p-2 border rounded mb-2 {{ $selectedContact == $contact->id ? 'border-primary bg-light' : 'border-light' }}"
                                 style="cursor: pointer;"
                                 wire:click="selectContact({{ $contact->id }})">

                                <div class="contact-avatar me-3">
                                    @if($contact->avatar)
                                        <img src="{{ asset('storage/' . $contact->avatar) }}"
                                             alt="{{ $contact->first_name }}"
                                             class="rounded-circle"
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                    @else
                                        <div class="avatar-placeholder rounded-circle d-flex align-items-center justify-content-center"
                                             style="width: 40px; height: 40px; background: #e9ecef; font-weight: 600;">
                                            {{ strtoupper(substr($contact->first_name, 0, 1)) }}{{ strtoupper(substr($contact->last_name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>

                                <div class="contact-info flex-grow-1">
                                    <div class="fw-semibold">{{ $contact->first_name }} {{ $contact->last_name }}</div>
                                    <div class="text-muted small">{{ $contact->email }}</div>
                                </div>

                                @if($selectedContact == $contact->id)
                                    <i class="fas fa-check-circle text-primary"></i>
                                @endif
                            </div>
                        @empty
                            <div class="text-center p-4 text-muted">
                                <i class="fas fa-search fa-2x mb-3"></i>
                                <p>No contacts found. Try a different search term.</p>
                            </div>
                        @endforelse
                    </div>

                    @error('selectedContact')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="mount">
                        Cancel
                    </button>
                    <button wire:click="createConversation"
                            class="btn btn-primary"
                            @if(!$selectedContact) disabled @endif>
                        Start Conversation
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
    .contact-item:hover {
        background-color: #f8f9fa !important;
    }

    .contact-item.border-primary {
        background-color: #e7f3ff !important;
    }
    </style>
</div>
