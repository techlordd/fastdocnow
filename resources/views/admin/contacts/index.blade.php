@extends('layouts.app')

@section('title', '- Contact Management')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Contact Management</h4>
                    <button class="btn btn-primary" onclick="openContactModal()">
                        <i class="fas fa-plus me-2"></i>Add Contact
                    </button>
                </div>
                <div class="card-body">
                    <!-- Contacts Table -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="contactsTable">
                            <thead>
                                <tr>
                                    <th width="50">Order</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Assigned Staff</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th width="150">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="contactsTableBody">
                                @foreach($contacts as $contact)
                                <tr data-contact-id="{{ $contact->id }}">
                                    <td>
                                        <span class="drag-handle" style="cursor: move;">
                                            <i class="fas fa-grip-vertical text-muted"></i>
                                        </span>
                                        {{ $contact->sort_order }}
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $contact->name }}</strong>
                                            @if($contact->description)
                                            <br><small class="text-muted">{{ $contact->description }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($contact->type === 'doctor')
                                        <span class="badge bg-primary">
                                            <i class="fas fa-user-md me-1"></i>Doctor
                                        </span>
                                        @else
                                        <span class="badge bg-success">
                                            <i class="fas fa-headset me-1"></i>Support
                                        </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($contact->assignedUser)
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-2" style="width: 30px; height: 30px; font-size: 12px;">
                                                {{ strtoupper(substr($contact->assignedUser->first_name, 0, 1)) }}
                                            </div>
                                            <div>
                                                {{ $contact->assignedUser->first_name }} {{ $contact->assignedUser->last_name }}
                                                @if($contact->assignedUser->wp_user_id)
                                                <span class="badge bg-info ms-1" title="WordPress User">WP</span>
                                                @endif
                                                <br><small class="text-muted">{{ $contact->assignedUser->email }}</small>
                                            </div>
                                        </div>
                                        @else
                                        <span class="text-warning">
                                            <i class="fas fa-exclamation-triangle me-1"></i>Unassigned
                                        </span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                {{ $contact->is_active ? 'checked' : '' }}
                                                onchange="toggleContactStatus({{ $contact->id }}, this.checked)">
                                            <label class="form-check-label">
                                                {{ $contact->is_active ? 'Active' : 'Inactive' }}
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        {{ $contact->created_at->format('M d, Y') }}
                                        <br><small class="text-muted">by {{ $contact->createdBy->first_name }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary"
                                                onclick="editContact({{ $contact->id }}, {{ json_encode($contact->load('assignedUser')) }})"
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger"
                                                onclick="deleteContact({{ $contact->id }}, '{{ $contact->name }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Modal -->
<div class="modal fade" id="contactModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contactModalTitle">Add Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="contactForm">
                    <input type="hidden" id="contactId" name="contact_id">

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Contact Name *</label>
                                <input type="text" class="form-control" id="contactName" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Type *</label>
                                <select class="form-select" id="contactType" name="type" required>
                                    <option value="doctor">Doctor</option>
                                    <option value="support">Support</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="contactDescription" name="description" rows="3"
                            placeholder="Brief description of what this contact handles..."></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3" id="contactAssigneeDiv">
                                <label class="form-label">Assign Staff Member</label>
                                <select class="form-select select2-users" id="assignedUser" name="assigned_user_id">
                                    <option value="">Select a staff member...</option>

                                    @if($users->count() > 0)
                                    <optgroup label="Laravel Users ({{ $users->count() }})">
                                        @foreach($users as $user)
                                        <option value="{{ $user->id }}">
                                            {{ $user->first_name }} {{ $user->last_name }} ({{ $user->email }})
                                        </option>
                                        @endforeach
                                    </optgroup>
                                    @endif

                                    @if(isset($wordpressUsers) && $wordpressUsers->count() > 0)
                                    <optgroup label="WordPress Users ({{ $wordpressUsers->count() }})">
                                        @foreach($wordpressUsers as $wpUser)
                                        <option value="{{ $wpUser->id }}">
                                            {{ $wpUser->first_name }} {{ $wpUser->last_name }} ({{ $wpUser->email }}) - WP: {{ $wpUser->username }}
                                        </option>
                                        @endforeach
                                    </optgroup>
                                    @else
                                    <optgroup label="WordPress Users (0)">
                                        <option disabled>No WordPress users available</option>
                                    </optgroup>
                                    @endif
                                </select>

                                @if(config('app.debug'))
                                <small class="text-muted mt-1 d-block">
                                    Debug: Laravel users: {{ $users->count() ?? 0 }},
                                    WordPress users: {{ isset($wordpressUsers) ? $wordpressUsers->count() : 'undefined' }}
                                </small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="sortOrder" name="sort_order"
                                    min="0" value="0">
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="isActive" name="is_active" value="1">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveContact()">
                    <span id="saveButtonText">Save Contact</span>
                    <span id="saveButtonSpinner" class="spinner-border spinner-border-sm ms-2 d-none"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link href="{{ asset('css/select2.min.css') }}" rel="stylesheet" />
<script src="{{ asset('js/select2.min.js') }}"></script>
<script>
    // Toast functions using SweetAlert2
    function showSuccessToast(message) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
        Toast.fire({
            icon: 'success',
            title: message
        });
    }

    function showErrorToast(message) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true
        });
        Toast.fire({
            icon: 'error',
            title: message
        });
    }

    function confirmDelete(title = 'Are you sure?', text = 'This action cannot be undone.') {
        return Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        });
    }

    // Make these functions globally available
    function openContactModal(contact = null) {
        const modal = new bootstrap.Modal(document.getElementById('contactModal'));
        const form = document.getElementById('contactForm');

        form.reset();
        document.getElementById('contactId').value = '';
        document.getElementById('contactModalTitle').textContent = 'Add Contact';
        document.getElementById('saveButtonText').textContent = 'Save Contact';

        $('#assignedUser').val(null).trigger('change');

        if (contact) {
            document.getElementById('contactModalTitle').textContent = 'Edit Contact';
            document.getElementById('saveButtonText').textContent = 'Update Contact';
            document.getElementById('contactId').value = contact.id;
            document.getElementById('contactName').value = contact.name;
            document.getElementById('contactDescription').value = contact.description || '';
            document.getElementById('contactType').value = contact.type;

            // Handle assigned user - check if it's a WordPress user
            let assignedUserId = contact.assigned_user_id || '';
            if (contact.assigned_user && contact.assigned_user.wp_user_id) {
                // If the assigned user is a WordPress user, use the wp_ prefix
                assignedUserId = 'wp_' + contact.assigned_user.wp_user_id;
            }
            $('#assignedUser').val(assignedUserId).trigger('change');

            document.getElementById('sortOrder').value = contact.sort_order || 0;
            document.getElementById('isActive').checked = contact.is_active;
        }

        modal.show();
    }

    function editContact(id, contact) {
        if (typeof contact === 'string') {
            try {
                contact = JSON.parse(contact);
            } catch (e) {
                console.error('Error parsing contact data:', e);
                showErrorToast('Error loading contact data');
                return;
            }
        }
        openContactModal(contact);
    }

    function saveContact() {
        const form = document.getElementById('contactForm');
        const formData = new FormData(form);
        const contactId = document.getElementById('contactId').value;
        const isEdit = contactId !== '';

        const saveButton = document.querySelector('#contactModal .btn-primary');
        const saveButtonSpinner = document.getElementById('saveButtonSpinner');

        saveButton.disabled = true;
        saveButtonSpinner.classList.remove('d-none');

        const data = {
            name: formData.get('name'),
            description: formData.get('description'),
            type: formData.get('type'),
            assigned_user_id: formData.get('assigned_user_id') || null,
            sort_order: parseInt(formData.get('sort_order')) || 0,
            is_active: formData.has('is_active')
        };

        fetch(isEdit ? `/admin/contacts/${contactId}` : '/admin/contacts', {
                method: isEdit ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showSuccessToast(data.message);
                    bootstrap.Modal.getInstance(document.getElementById('contactModal')).hide();
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showErrorToast(data.message || 'An error occurred');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                showErrorToast('An error occurred while saving the contact');
            })
            .finally(() => {
                saveButton.disabled = false;
                saveButtonSpinner.classList.add('d-none');
            });
    }

    function deleteContact(id, name) {
        confirmDelete('Delete Contact?', `Are you sure you want to delete "${name}"? This will also delete all associated conversations.`)
            .then((result) => {
                if (result.isConfirmed) {
                    fetch(`/admin/contacts/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                showSuccessToast(data.message);
                                setTimeout(() => window.location.reload(), 1000);
                            } else {
                                showErrorToast(data.message || 'An error occurred');
                            }
                        })
                        .catch(err => {
                            console.error('Error:', err);
                            showErrorToast('An error occurred while deleting the contact');
                        });
                }
            });
    }

    function toggleContactStatus(id, isActive, event) {
        fetch(`/admin/contacts/${id}/toggle-status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showSuccessToast(data.message);
                    const label = event.target.nextElementSibling;
                    label.textContent = data.is_active ? 'Active' : 'Inactive';
                } else {
                    showErrorToast(data.message || 'An error occurred');
                    event.target.checked = !isActive;
                }
            })
            .catch(err => {
                console.error('Error:', err);
                showErrorToast('An error occurred');
                event.target.checked = !isActive;
            });
    }

    function updateContactOrder() {
        const rows = document.querySelectorAll('#contactsTableBody tr');
        const contacts = Array.from(rows).map((row, index) => ({
            id: parseInt(row.dataset.contactId),
            sort_order: index
        }));

        fetch('/admin/contacts/update-order', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    contacts
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showSuccessToast(data.message);
                } else {
                    showErrorToast(data.message || 'Failed to update order');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                showErrorToast('An error occurred while updating order');
            });
    }

    // Initialize after DOM load
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof Sortable === 'undefined') {
            console.error('SortableJS is not loaded');
            return;
        }
        const tbody = document.getElementById('contactsTableBody');
        if (tbody) {
            new Sortable(tbody, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: updateContactOrder
            });
        }

        if (typeof $ !== 'undefined') {
            $('#assignedUser').select2({
                placeholder: 'Select a staff member...',
                allowClear: true,
                dropdownParent: $('#contactAssigneeDiv'), 
                minimumResultsForSearch: 0
            });
        }
    });
</script>

@endpush
