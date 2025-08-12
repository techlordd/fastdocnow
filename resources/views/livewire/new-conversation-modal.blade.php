<div>

    <div class="modal-body p-0">
        <!-- Search Section -->
        <div class="search-section p-4 border-bottom bg-white">
            <div class="position-relative">
                <input type="text"
                    id="contactSearch"
                    wire:model.live="searchTerm"
                    placeholder="Search contacts by name or description..."
                    class="form-control form-control-lg ps-5">
                <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            </div>
        </div>

        <!-- Contacts Grid -->
        <div class="contacts-grid p-4" style="max-height: calc(100vh - 260px); overflow-y: auto;">
            @if(count($this->availableContacts) > 0)
            <div class="row g-3">
                @foreach($this->availableContacts as $contact)
                <div class="col-md-6 col-lg-4" wire:key="contact-{{ $contact['id'] }}" >
                    <div class="contact-card {{ $selectedContact == $contact['id'] ? 'selected' : '' }}"
                        wire:click="selectContact({{ $contact['id'] }})">

                        <!-- Contact Avatar with Type Icon -->
                        <div class="contact-avatar-wrapper mb-3">
                            <div class="contact-avatar avatar-placeholder">
                                @if($contact['type'] === 'doctor')
                                <i class="fas fa-user-md"></i>
                                @elseif($contact['type'] === 'support')
                                <i class="fas fa-headset"></i>
                                @else
                                <i class="fas fa-user"></i>
                                @endif
                            </div>

                            <!-- Selection Indicator -->
                            @if($selectedContact == $contact['id'])
                            <div class="selection-indicator">
                                <i class="fas fa-check"></i>
                            </div>
                            @endif
                        </div>

                        <!-- Contact Info -->
                        <div class="contact-info text-center">
                            <!-- Contact Type Badge (matching sidebar) -->
                            <div class="contact-type-badge mb-2">
                                @if($contact['type'] === 'doctor')
                                <span class="badge bg-primary">
                                    <i class="fas fa-user-md text-white me-1"></i>Doctor
                                </span>
                                @elseif($contact['type'] === 'support')
                                <span class="badge bg-success">
                                    <i class="fas fa-headset text-white me-1"></i>Support
                                </span>
                                @else
                                <span class="badge bg-secondary">
                                    <i class="fas fa-user text-white me-1"></i>{{ ucfirst($contact['type']) }}
                                </span>
                                @endif
                            </div>

                            <h6 class="contact-name mb-2">{{ $contact['name'] }}</h6>

                            <!-- Description -->
                            @if($contact['description'])
                            <p class="contact-description text-muted mt-2 small">
                                {{ Str::limit($contact['description'], 80) }}
                            </p>
                            @endif

                            <!-- Assigned User (matching sidebar logic) -->
                            @if($contact['assigned_user'])
                            <div class="assigned-user mt-3">
                                <div class="assigned-user-info">
                                    <small class="text-muted d-block">Handled by:</small>
                                    <strong class="assigned-user-name">{{ $contact['assigned_user']['name'] }}</strong>
                                    @if($contact['assigned_user']['is_online'])
                                    <span class="badge bg-success ms-1" style="font-size: 8px;">Online</span>
                                    @else
                                    <div class="text-muted small">
                                        Last seen {{ \Carbon\Carbon::parse($contact['assigned_user']['last_seen_at'])->diffForHumans() }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @else
                            <div class="text-warning mt-3">
                                <small><i class="fas fa-exclamation-triangle me-1"></i>No staff assigned</small>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="empty-state text-center py-5">
                <div class="empty-icon mb-3">
                    @if($searchTerm)
                    <i class="fas fa-search fa-3x text-muted"></i>
                    @else
                    <i class="fas fa-address-book fa-3x text-muted"></i>
                    @endif
                </div>
                <h5 class="text-muted mb-2">
                    @if($searchTerm)
                    No contacts found
                    @else
                    No contacts available
                    @endif
                </h5>
                <p class="text-muted">
                    @if($searchTerm)
                    Try a different search term or check your spelling.
                    @else
                    No contacts are available for conversations.<br>
                    <small>Contact your administrator to set up support contacts.</small>
                    @endif
                </p>
            </div>
            @endif
        </div>

        @error('selectedContact')
        <div class="alert alert-danger mx-4 mb-0">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ $message }}
        </div>
        @enderror
    </div>

    <style>
        /* Modal Enhancements */
        .modal-lg {
            max-width: 900px;
        }

        .modal-header {
            border-bottom: 1px solid #e9ecef;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .modal-footer {
            border-top: 1px solid #e9ecef;
            background: #f8f9fa;
        }

        /* Search Section */
        .search-section .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-size: 16px;
        }

        .search-section .form-control:focus {
            border-color: #6600ff;
            box-shadow: 0 0 0 0.2rem rgba(102, 0, 255, 0.15);
        }

        /* Contact Cards - Enhanced Design */
        .contact-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 20px;
            padding: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            height: 100%;
            min-height: 280px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .contact-card:hover {
            border-color: #6600ff;
            box-shadow: 0 12px 35px rgba(102, 0, 255, 0.15);
            transform: translateY(-3px);
        }

        .contact-card.selected {
            border-color: #6600ff;
            background: linear-gradient(135deg, #f8f4ff 0%, #ede7ff 100%);
            box-shadow: 0 12px 35px rgba(102, 0, 255, 0.25);
            transform: translateY(-3px);
        }

        /* Avatar Styling - Matching Sidebar */
        .contact-avatar-wrapper {
            position: relative;
            display: flex;
            justify-content: center;
        }

        .avatar-placeholder {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #6600ff 0%, #4400cc 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 28px;
            border: 4px solid #fff;
            box-shadow: 0 4px 15px rgba(102, 0, 255, 0.2);
            transition: all 0.3s ease;
        }

        .contact-card:hover .avatar-placeholder,
        .contact-card.selected .avatar-placeholder {
            box-shadow: 0 6px 25px rgba(102, 0, 255, 0.35);
            transform: scale(1.05);
        }

        /* Selection Indicator */
        .selection-indicator {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 28px;
            height: 28px;
            background: #22c55e;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            border: 3px solid white;
            box-shadow: 0 2px 12px rgba(34, 197, 94, 0.4);
            animation: bounceIn 0.3s ease;
        }

        @keyframes bounceIn {
            0% {
                transform: scale(0);
            }

            50% {
                transform: scale(1.2);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Contact Info */
        .contact-name {
            color: #2d3748;
            font-weight: 700;
            font-size: 17px;
            margin-bottom: 8px;
        }

        .contact-description {
            font-size: 13px;
            line-height: 1.5;
            color: #718096;
            text-align: center;
        }

        /* Type Badges - Matching Sidebar */
        .contact-type-badge .badge {
            font-size: 11px;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 12px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* Assigned User Section */
        .assigned-user {
            background: #f7fafc;
            border-radius: 12px;
            padding: 12px;
            width: 100%;
            border: 1px solid #e2e8f0;
        }

        .assigned-user-name {
            color: #4a5568;
            font-size: 13px;
            font-weight: 600;
        }

        .assigned-user-info small {
            font-size: 11px;
            color: #a0aec0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Empty State */
        .empty-state .empty-icon {
            opacity: 0.4;
            margin-bottom: 20px;
        }

        .empty-state h5 {
            color: #4a5568;
            font-weight: 600;
        }

        /* Scrollbar Styling */
        .contacts-grid::-webkit-scrollbar {
            width: 8px;
        }

        .contacts-grid::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 12px;
        }

        .contacts-grid::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #cbd5e0, #a0aec0);
            border-radius: 12px;
        }

        .contacts-grid::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #a0aec0, #718096);
        }

        /* Button Styling */
        .btn {
            border-radius: 10px;
            font-weight: 600;
            padding: 12px 24px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6600ff 0%, #4400cc 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(102, 0, 255, 0.2);
        }

        .btn-primary:hover:not(:disabled) {
            background: linear-gradient(135deg, #5500dd 0%, #3300aa 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 0, 255, 0.3);
        }

        .btn-primary:disabled {
            background: #e2e8f0;
            color: #a0aec0;
            box-shadow: none;
            cursor: not-allowed;
        }

        .btn-outline-secondary {
            border: 2px solid #e2e8f0;
            color: #4a5568;
            background: white;
        }

        .btn-outline-secondary:hover {
            background: #f7fafc;
            border-color: #cbd5e0;
            color: #2d3748;
            transform: translateY(-1px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .modal-lg {
                max-width: 95%;
                margin: 10px;
            }

            .contact-card {
                min-height: 240px;
                padding: 20px;
            }

            .avatar-placeholder {
                width: 56px;
                height: 56px;
                font-size: 22px;
            }

            .contact-name {
                font-size: 15px;
            }
        }

        /* Loading States */
        .contact-card.loading {
            opacity: 0.7;
            pointer-events: none;
        }

        /* Focus States for Accessibility */
        .contact-card:focus {
            outline: 2px solid #6600ff;
            outline-offset: 2px;
        }
    </style>
</div>