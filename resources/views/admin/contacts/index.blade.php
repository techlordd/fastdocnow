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
                                                        onclick="editContact({{ $contact->id }}, {{ json_encode($contact) }})">
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
                            <div class="mb-3">
                                <label class="form-label">Assign Staff Member</label>
                                <select class="form-select" id="assignedUser" name="assigned_user_id">
                                    <option value="">Select a staff member...</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">
                                            {{ $user->first_name }} {{ $user->last_name }} ({{ $user->email }})
                                        </option>
                                    @endforeach
                                </select>
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

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                        <label class="form-check-label" for="isActive">
                            Active (visible to users)
                        </label>
                    </div>
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
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
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
// Initialize sortable table
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.getElementById('contactsTableBody');
    new Sortable(tbody, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function(evt) {
            updateContactOrder();
        }
    });
});

function openContactModal(contact = null) {
    const modal = new bootstrap.Modal(document.getElementById('contactModal'));
    const form = document.getElementById('contactForm');

    // Reset form
    form.reset();
    document.getElementById('contactId').value = '';
    document.getElementById('contactModalTitle').textContent = 'Add Contact';
    document.getElementById('saveButtonText').textContent = 'Save Contact';

    if (contact) {
        // Edit mode
        document.getElementById('contactModalTitle').textContent = 'Edit Contact';
        document.getElementById('saveButtonText').textContent = 'Update Contact';
        document.getElementById('contactId').value = contact.id;
        document.getElementById('contactName').value = contact.name;
        document.getElementById('contactDescription').value = contact.description || '';
        document.getElementById('contactType').value = contact.type;
        document.getElementById('assignedUser').value = contact.assigned_user_id || '';
        document.getElementById('sortOrder').value = contact.sort_order || 0;
        document.getElementById('isActive').checked = contact.is_active;
    }

    modal.show();
}

function editContact(id, contact) {
    // Ensure contact object is properly parsed
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

    // Show loading state
    const saveButton = document.querySelector('#contactModal .btn-primary');
    const saveButtonText = document.getElementById('saveButtonText');
    const saveButtonSpinner = document.getElementById('saveButtonSpinner');

    saveButton.disabled = true;
    saveButtonSpinner.classList.remove('d-none');

    // Prepare data
    const data = {
        name: formData.get('name'),
        description: formData.get('description'),
        type: formData.get('type'),
        assigned_user_id: formData.get('assigned_user_id') || null,
        sort_order: parseInt(formData.get('sort_order')) || 0,
        is_active: formData.has('is_active')
    };

    const url = isEdit ? `/admin/contacts/${contactId}` : '/admin/contacts';
    const method = isEdit ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessToast(data.message);
            bootstrap.Modal.getInstance(document.getElementById('contactModal')).hide();
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showErrorToast(data.message || 'An error occurred');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorToast('An error occurred while saving the contact')
    })
    .finally(() => {
        saveButton.disabled = false;
        saveButtonSpinner.classList.add('d-none');
    });
}

function deleteContact(id, name) {
    confirmDelete(
        'Delete Contact?',
        `Are you sure you want to delete "${name}"? This will also delete all associated conversations.`
    ).then((result) => {
        if (result.isConfirmed) {
            fetch(`/admin/contacts/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessToast(data.message);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showErrorToast(data.message || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorToast('An error occurred while deleting the contact');
            });
        }
    });
}

function toggleContactStatus(id, isActive) {
    fetch(`/admin/contacts/${id}/toggle-status`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessToast(data.message);
            // Update the label
            const switchElement = event.target;
            const label = switchElement.nextElementSibling;
            label.textContent = data.is_active ? 'Active' : 'Inactive';
        } else {
            showErrorToast(data.message || 'An error occurred');
            // Revert the switch
            event.target.checked = !isActive;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorToast('An error occurred');
        // Revert the switch
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
        body: JSON.stringify({ contacts })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessToast(data.message);
        } else {
            showErrorToast(data.message || 'Failed to update order');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorToast('An error occurred while updating order');
    });
}
</script>
@endpush
