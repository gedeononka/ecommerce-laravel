<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Display user profile.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Statistiques utilisateur
        $stats = [
            'total_orders' => Order::where('user_id', $user->id)->count(),
            'total_spent' => Order::where('user_id', $user->id)
                                 ->where('payment_status', 'paid')
                                 ->sum('total'),
            'pending_orders' => Order::where('user_id', $user->id)
                                   ->where('status', 'pending')
                                   ->count(),
            'completed_orders' => Order::where('user_id', $user->id)
                                     ->where('status', 'delivered')
                                     ->count()
        ];

        // Commandes récentes
        $recentOrders = Order::where('user_id', $user->id)
                            ->with('items.product')
                            ->latest()
                            ->limit(5)
                            ->get();

        return view('client.profile.index', compact('user', 'stats', 'recentOrders'));
    }

    /**
     * Show edit profile form.
     */
    public function edit()
    {
        $user = Auth::user();
        return view('client.profile.edit', compact('user'));
    }

    /**
     * Update user profile.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date|before:today',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $data = $request->except(['avatar']);

        // Traiter l'avatar
        if ($request->hasFile('avatar')) {
            // Supprimer l'ancien avatar
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Enregistrer le nouveau
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $avatarPath;
        }

        $user->update($data);

        return redirect()->route('profile.index')
                        ->with('success', 'Profil mis à jour avec succès.');
    }

    /**
     * Show change password form.
     */
    public function editPassword()
    {
        return view('client.profile.password');
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->with('error', 'Le mot de passe actuel est incorrect.');
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return redirect()->route('profile.index')
                        ->with('success', 'Mot de passe mis à jour avec succès.');
    }

    /**
     * Show address book.
     */
    public function addresses()
    {
        $user = Auth::user();
        return view('client.profile.addresses', compact('user'));
    }

    /**
     * Update user addresses.
     */
    public function updateAddresses(Request $request)
    {
        $request->validate([
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:100',
            'phone' => 'required|string|max:20'
        ]);

        $user = Auth::user();
        $user->update($request->only(['address', 'city', 'postal_code', 'country', 'phone']));

        return redirect()->back()->with('success', 'Adresse mise à jour avec succès.');
    }

    /**
     * Show order history.
     */
    public function orders()
    {
        $orders = Order::where('user_id', Auth::id())
                      ->with('items.product')
                      ->latest()
                      ->paginate(10);

        return view('client.profile.orders', compact('orders'));
    }

    /**
     * Show wishlist.
     */
    public function wishlist()
    {
        $user = Auth::user();
        $wishlistItems = $user->wishlist()->with('product')->get();
        
        return view('client.profile.wishlist', compact('wishlistItems'));
    }

    /**
     * Download user data.
     */
    public function downloadData()
    {
        $user = Auth::user();
        
        $userData = [
            'profile' => $user->toArray(),
            'orders' => Order::where('user_id', $user->id)
                           ->with('items.product')
                           ->get()
                           ->toArray(),
            'reviews' => $user->reviews()->with('product')->get()->toArray()
        ];

        $fileName = 'user_data_' . $user->id . '_' . date('Y-m-d') . '.json';
        
        return response()->json($userData)
                        ->header('Content-Type', 'application/json')
                        ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    /**
     * Show account deletion form.
     */
    public function deleteAccount()
    {
        return view('client.profile.delete-account');
    }

    /**
     * Delete user account.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'confirmation' => 'required|in:DELETE'
        ]);

        $user = Auth::user();

        if (!Hash::check($request->password, $user->password)) {
            return redirect()->back()->with('error', 'Mot de passe incorrect.');
        }

        // Supprimer l'avatar
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Anonymiser les commandes au lieu de les supprimer
        Order::where('user_id', $user->id)->update([
            'user_id' => null,
            'shipping_address' => 'Compte supprimé',
            'phone' => null
        ]);

        // Supprimer le compte
        $user->delete();

        return redirect()->route('home')->with('success', 'Votre compte a été supprimé avec succès.');
    }
}