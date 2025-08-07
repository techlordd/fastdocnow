<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    public function index()
    {
        $contacts = Contact::with(['assignedUser', 'createdBy'])
            ->ordered()
            ->get();

        $users = User::where('id', '!=', Auth::id())
            ->orderBy('first_name')
            ->get();

        return view('admin.contacts.index', compact('contacts', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:doctor,support',
            'assigned_user_id' => 'nullable|exists:users,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        Contact::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'assigned_user_id' => $request->assigned_user_id,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contact created successfully!'
        ]);
    }

    public function update(Request $request, Contact $contact)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:doctor,support',
            'assigned_user_id' => 'nullable|exists:users,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $contact->update([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'assigned_user_id' => $request->assigned_user_id,
            'sort_order' => $request->sort_order ?? $contact->sort_order,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contact updated successfully!'
        ]);
    }

    public function destroy(Contact $contact)
    {
        // Delete all conversations associated with this contact
        $contact->conversations()->delete();
        
        $contact->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact deleted successfully!'
        ]);
    }

    public function toggleStatus(Contact $contact)
    {
        $contact->update([
            'is_active' => !$contact->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contact status updated successfully!',
            'is_active' => $contact->is_active
        ]);
    }

    public function updateOrder(Request $request)
    {
        $request->validate([
            'contacts' => 'required|array',
            'contacts.*.id' => 'required|exists:contacts,id',
            'contacts.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($request->contacts as $contactData) {
            Contact::where('id', $contactData['id'])
                ->update(['sort_order' => $contactData['sort_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contact order updated successfully!'
        ]);
    }
}
