<div>
    <!-- Step Indicator -->
    <div class="d-flex align-items-center justify-content-center mb-4">
        <div class="d-flex align-items-center">
            <div class="rounded-circle d-flex align-items-center justify-content-center"
                 style="width: 32px; height: 32px; font-size: 14px; font-weight: 600; {{ $step >= 1 ? 'background: #6600ff; color: white;' : 'background: #e9ecef; color: #6c757d;' }}">
                1
            </div>
            <span class="ms-2 small {{ $step >= 1 ? 'text-dark' : 'text-muted' }}">Type</span>
        </div>
        <div class="mx-3" style="width: 32px; height: 2px; {{ $step >= 2 ? 'background: #6600ff;' : 'background: #e9ecef;' }}"></div>
        <div class="d-flex align-items-center">
            <div class="rounded-circle d-flex align-items-center justify-content-center"
                 style="width: 32px; height: 32px; font-size: 14px; font-weight: 600; {{ $step >= 2 ? 'background: #6600ff; color: white;' : 'background: #e9ecef; color: #6c757d;' }}">
                2
            </div>
            <span class="ms-2 small {{ $step >= 2 ? 'text-dark' : 'text-muted' }}">{{ $conversationType === 'group' ? 'Users' : 'User' }}</span>
        </div>
    </div>

    @if($step === 1)
        <!-- Step 1: Choose Type -->
        <div>
            <h5 class="mb-4">Choose conversation type:</h5>
            <div class="row g-3">
                <div class="col-6">
                    <button wire:click="selectType('private')"
                            class="btn w-100 h-100 p-4 border border-2 rounded-3 position-relative"
                            style="border-color: #dee2e6; transition: all 0.3s ease;"
                            onmouseover="this.style.borderColor='#6600ff'; this.style.backgroundColor='rgba(102, 0, 255, 0.05)';"
                            onmouseout="this.style.borderColor='#dee2e6'; this.style.backgroundColor='transparent';">
                        <div class="text-center">
                            <div class="d-flex align-items-center justify-content-center mx-auto mb-3 rounded-3"
                                 style="width: 48px; height: 48px; background-color: rgba(13, 110, 253, 0.1);">
                                <i class="fas fa-user text-primary fs-4"></i>
                            </div>
                            <h6 class="fw-semibold mb-2">Private Chat</h6>
                            <small class="text-muted">One-on-one conversation</small>
                        </div>
                    </button>
                </div>

                <div class="col-6">
                    <button wire:click="selectType('group')"
                            class="btn w-100 h-100 p-4 border border-2 rounded-3 position-relative"
                            style="border-color: #dee2e6; transition: all 0.3s ease;"
                            onmouseover="this.style.borderColor='#6600ff'; this.style.backgroundColor='rgba(102, 0, 255, 0.05)';"
                            onmouseout="this.style.borderColor='#dee2e6'; this.style.backgroundColor='transparent';">
                        <div class="text-center">
                            <div class="d-flex align-items-center justify-content-center mx-auto mb-3 rounded-3"
                                 style="width: 48px; height: 48px; background-color: rgba(25, 135, 84, 0.1);">
                                <i class="fas fa-users text-success fs-4"></i>
                            </div>
                            <h6 class="fw-semibold mb-2">Group Chat</h6>
                            <small class="text-muted">Chat with multiple people</small>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    @elseif($step === 2)
        <!-- Step 2: Select Users -->
        <div>
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h5>
                    {{ $conversationType === 'group' ? 'Select users for group:' : 'Select user:' }}
                </h5>
                <button wire:click="goBack" class="btn btn-link text-muted p-0">
                    <i class="fas fa-arrow-left me-2"></i> Back
                </button>
            </div>

            @if($conversationType === 'group')
                <!-- Group Title -->
                <div class="mb-3">
                    <label for="groupTitle" class="form-label">Group Name</label>
                    <input type="text"
                           id="groupTitle"
                           wire:model="groupTitle"
                           placeholder="Enter group name"
                           class="form-control">
                    @error('groupTitle')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            @endif

            <!-- Search Users -->
            <div class="mb-3">
                <div class="position-relative">
                    <input type="text"
                           wire:model.live.debounce.300ms="searchTerm"
                           placeholder="Search users..."
                           class="form-control ps-5">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                </div>
            </div>

            <!-- Users List -->
            <div class="overflow-auto custom-scrollbar" style="max-height: 300px;">
                @forelse($this->availableUsers as $user)
                    <div class="user-item {{ in_array($user->id, $selectedUsers) ? 'selected' : '' }}"
                         wire:click="toggleUser({{ $user->id }})"
                         style="cursor: pointer;">

                        @if($conversationType === 'group')
                            <input type="checkbox"
                                   {{ in_array($user->id, $selectedUsers) ? 'checked' : '' }}
                                   class="form-check-input me-3">
                        @endif

                        <!-- Avatar -->
                        <div class="user-avatar">
                            @if($user->avatar)
                                <img src="{{ $user->avatar }}" alt="{{ $user->first_name }}" class="w-100 h-100 rounded-circle" style="object-fit: cover;">
                            @else
                                {{ strtoupper(substr($user->first_name, 0, 1)) }}{{ strtoupper(substr($user->last_name, 0, 1)) }}
                            @endif
                        </div>

                        <!-- User Info -->
                        <div class="user-info flex-fill">
                            <h6 class="mb-0">{{ $user->first_name }} {{ $user->last_name }}</h6>
                            <small class="text-muted">@{{ $user->username }}</small>
                        </div>

                        <!-- Online Status -->
                        <div class="ms-2">
                            @if($user->last_seen_at > now()->subMinutes(5))
                                <div class="online-indicator" style="width: 12px; height: 12px;"></div>
                            @else
                                <div class="rounded-circle" style="width: 12px; height: 12px; background: #dee2e6;"></div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <div class="welcome-icon mx-auto mb-3" style="width: 48px; height: 48px;">
                            <i class="fas fa-search"></i>
                        </div>
                        <p class="text-muted">No users found</p>
                    </div>
                @endforelse
            </div>

            <!-- Selected Users Count -->
            @if($conversationType === 'group' && count($selectedUsers) > 0)
                <div class="mt-3 p-3 bg-light rounded">
                    <small class="text-muted">
                        {{ count($selectedUsers) }} user{{ count($selectedUsers) !== 1 ? 's' : '' }} selected
                    </small>
                </div>
            @endif

            <!-- Create Button -->
            <div class="mt-4 d-flex justify-content-end">
                <button wire:click="createConversation"
                        {{ count($selectedUsers) === 0 ? 'disabled' : '' }}
                        class="btn btn-primary">
                    Create {{ $conversationType === 'group' ? 'Group' : 'Chat' }}
                </button>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', function() {
        Livewire.on('conversationCreated', function(conversationId) {
            loadConversation(conversationId);
            $('#newConversationModal').close();
        });
    });
</script>
@endpush
