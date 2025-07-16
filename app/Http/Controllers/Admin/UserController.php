<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function index(Request $request)
    {
        $query = User::query();
        
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }
        
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        $users = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load(['orders' => function($query) {
            $query->with('orderItems.product')->latest();
        }]);
        
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,client',
            'password' => 'nullable|min:8|confirmed',
        ]);

        $data = $request->only(['name', 'email', 'role']);
        
        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);
        
        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur mis à jour avec succès');
    }

    public function destroy(User $user)
    {
        if ($user->orders()->exists()) {
            return redirect()->back()
                ->with('error', 'Impossible de supprimer un utilisateur avec des commandes');
        }

        $user->delete();
        
        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur supprimé avec succès');
    }

    public function toggleStatus(User $user)
    {
        $user->is_active = !$user->is_active;
        $user->save();
        
        return redirect()->back()
            ->with('success', 'Statut utilisateur mis à jour');
    }
}