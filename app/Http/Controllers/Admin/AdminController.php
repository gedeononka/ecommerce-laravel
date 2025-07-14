<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Display admin dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::where('role', 'client')->count(),
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'total_categories' => Category::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'recent_orders' => Order::with('user')->latest()->limit(5)->get(),
            'low_stock_products' => Product::where('stock', '<', 10)->limit(5)->get(),
            'monthly_revenue' => Order::where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->sum('total_amount')
        ];

        return view('admin.dashboard', compact('stats'));
    }

    /**
     * Show admin profile
     */
    public function profile()
    {
        $admin = Auth::user();
        return view('admin.profile', compact('admin'));
    }

    /**
     * Update admin profile
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
            'password' => 'nullable|min:8|confirmed'
        ]);

        $admin = Auth::user();
        $admin->name = $request->name;
        $admin->email = $request->email;
        
        if ($request->filled('password')) {
            $admin->password = bcrypt($request->password);
        }
        
        $admin->save();

        return redirect()->back()->with('success', 'Profil mis à jour avec succès');
    }

    /**
     * Show system settings
     */
    public function settings()
    {
        return view('admin.settings');
    }

    /**
     * Update system settings
     */
    public function updateSettings(Request $request)
    {
        // Logic for updating system settings
        return redirect()->back()->with('success', 'Paramètres mis à jour avec succès');
    }
}