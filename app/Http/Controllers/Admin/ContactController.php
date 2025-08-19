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

        // Get existing Laravel users (excluding current user)
        $users = User::where('id', '!=', Auth::id())
            ->orderBy('first_name')
            ->get();

        // Get WordPress users and convert them to a collection with needed properties
        $wordpressUsers = collect();
        try {
            // Get active WordPress users (simplified query)
            $wpUsers = \App\Models\WordPressUser::where('user_status', 0)
                ->whereNotNull('user_email')
                ->where('user_email', '!=', '')
                ->orderBy('display_name')
                ->limit(100) // Limit to first 100 for performance
                ->get();

            \Log::info('WordPress users query result', [
                'total_wp_users' => $wpUsers->count(),
                'sample_user' => $wpUsers->first() ? [
                    'ID' => $wpUsers->first()->ID,
                    'email' => $wpUsers->first()->user_email,
                    'login' => $wpUsers->first()->user_login,
                    'display_name' => $wpUsers->first()->display_name
                ] : null
            ]);

            // Convert to our format (remove canAccessChat filter for now)
            $wordpressUsers = $wpUsers->map(function($wpUser) {
                return (object) [
                    'id' => 'wp_' . $wpUser->ID,
                    'first_name' => $wpUser->first_name ?: ($wpUser->display_name ?: 'WordPress'),
                    'last_name' => $wpUser->last_name ?: 'User',
                    'email' => $wpUser->user_email,
                    'username' => $wpUser->user_login,
                    'is_wordpress_user' => true,
                    'wp_user_id' => $wpUser->ID,
                    'display_name' => $wpUser->display_name ?: $wpUser->user_nicename,
                ];
            });

            \Log::info('Processed WordPress users for contact assignment', [
                'filtered_count' => $wordpressUsers->count(),
                'sample_processed' => $wordpressUsers->first()
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to fetch WordPress users for contact assignment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return view('admin.contacts.index', compact('contacts', 'users', 'wordpressUsers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:doctor,support',
            'assigned_user_id' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Handle WordPress user assignment
        $assignedUserId = $this->resolveAssignedUserId($request->assigned_user_id);

        Contact::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'assigned_user_id' => $assignedUserId,
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
            'assigned_user_id' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Handle WordPress user assignment
        $assignedUserId = $this->resolveAssignedUserId($request->assigned_user_id);

        $contact->update([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'assigned_user_id' => $assignedUserId,
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

    /**
     * Resolve assigned user ID from request (handles both Laravel and WordPress users)
     */
    private function resolveAssignedUserId($assignedUserId)
    {
        if (empty($assignedUserId)) {
            return null;
        }

        // Check if it's a WordPress user (prefixed with 'wp_')
        if (str_starts_with($assignedUserId, 'wp_')) {
            $wpUserId = (int) str_replace('wp_', '', $assignedUserId);

            try {
                // Get the WordPress user
                $wpUser = \App\Models\WordPressUser::find($wpUserId);
                if (!$wpUser) {
                    return null;
                }

                // Convert WordPress user to Laravel user
                $laravelUser = $wpUser->toLaravelUser();
                return $laravelUser->id;
            } catch (\Exception $e) {
                \Log::error('Failed to convert WordPress user to Laravel user for contact assignment', [
                    'wp_user_id' => $wpUserId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        }

        // It's a regular Laravel user ID, validate it exists
        if (User::where('id', $assignedUserId)->exists()) {
            return (int) $assignedUserId;
        }

        return null;
    }
}
