<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Category;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Display the admin dashboard
     */
    public function index()
    {
        // Basic statistics
        $stats = [
            'total_users' => User::where('role', 'client')->count(),
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'total_categories' => Category::count(),
            'active_products' => Product::where('status', 'active')->count(),
            'inactive_products' => Product::where('status', 'inactive')->count(),
            'low_stock_products' => Product::where('stock', '<', 10)->count(),
            'out_of_stock_products' => Product::where('stock', '<=', 0)->count(),
        ];

        // Order statistics
        $orderStats = [
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'shipped_orders' => Order::where('status', 'shipped')->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'completed_orders' => Order::where('status', 'completed')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
        ];

        // Revenue statistics
        $revenueStats = [
            'total_revenue' => Order::where('status', 'completed')->sum('total_amount'),
            'monthly_revenue' => Order::where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_amount'),
            'daily_revenue' => Order::where('status', 'completed')
                ->whereDate('created_at', today())
                ->sum('total_amount'),
            'average_order_value' => Order::where('status', 'completed')->avg('total_amount'),
        ];

        // Recent data
        $recentOrders = Order::with(['user', 'orderItems'])
            ->latest()
            ->limit(10)
            ->get();

        $recentUsers = User::where('role', 'client')
            ->latest()
            ->limit(10)
            ->get();

        $lowStockProducts = Product::where('stock', '<', 10)
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get();

        // Charts data
        $salesChart = $this->getSalesChartData();
        $ordersChart = $this->getOrdersChartData();
        $topProducts = $this->getTopProducts();
        $topCategories = $this->getTopCategories();

        return view('admin.dashboard', compact(
            'stats',
            'orderStats',
            'revenueStats',
            'recentOrders',
            'recentUsers',
            'lowStockProducts',
            'salesChart',
            'ordersChart',
            'topProducts',
            'topCategories'
        ));
    }

    /**
     * Get sales chart data for the last 30 days
     */
    private function getSalesChartData()
    {
        $salesData = Order::where('status', 'completed')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $sales = [];
        $orders = [];

        // Fill in missing dates with 0 values
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('M d');
            
            $dayData = $salesData->firstWhere('date', $date);
            $sales[] = $dayData ? (float)$dayData->total : 0;
            $orders[] = $dayData ? (int)$dayData->count : 0;
        }

        return [
            'labels' => $labels,
            'sales' => $sales,
            'orders' => $orders
        ];
    }

    /**
     * Get orders chart data by status
     */
    private function getOrdersChartData()
    {
        $ordersData = Order::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];
        $data = [];
        $labels = [];
        $colors = [
            'pending' => '#fbbf24',
            'processing' => '#3b82f6',
            'shipped' => '#8b5cf6',
            'delivered' => '#06b6d4',
            'completed' => '#10b981',
            'cancelled' => '#ef4444'
        ];

        foreach ($statuses as $status) {
            $orderData = $ordersData->firstWhere('status', $status);
            if ($orderData && $orderData->count > 0) {
                $data[] = (int)$orderData->count;
                $labels[] = ucfirst($status);
            }
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => array_values($colors)
        ];
    }

    /**
     * Get top selling products
     */
    private function getTopProducts()
    {
        return OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->with('product')
            ->groupBy('product_id')
            ->orderBy('total_sold', 'desc')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'name' => $item->product->name,
                    'total_sold' => $item->total_sold,
                    'revenue' => $item->product->price * $item->total_sold
                ];
            });
    }

    /**
     * Get top categories by sales
     */
    private function getTopCategories()
    {
        return OrderItem::select('products.category_id', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->groupBy('products.category_id')
            ->orderBy('total_sold', 'desc')
            ->limit(10)
            ->get()
            ->map(function($item) {
                $category = Category::find($item->category_id);
                return [
                    'name' => $category->name,
                    'total_sold' => $item->total_sold
                ];
            });
    }

    /**
     * Get dashboard analytics data for AJAX requests
     */
    public function analytics(Request $request)
    {
        $period = $request->get('period', '30'); // days

        $startDate = Carbon::now()->subDays($period);

        $analytics = [
            'revenue' => Order::where('status', 'completed')
                ->where('created_at', '>=', $startDate)
                ->sum('total_amount'),
            'orders' => Order::where('created_at', '>=', $startDate)->count(),
            'users' => User::where('role', 'client')
                ->where('created_at', '>=', $startDate)
                ->count(),
            'products_sold' => OrderItem::whereHas('order', function($query) use ($startDate) {
                $query->where('created_at', '>=', $startDate);
            })->sum('quantity'),
        ];

        return response()->json($analytics);
    }

    /**
     * Get real-time notifications
     */
    public function notifications()
    {
        $notifications = [];

        // Low stock alerts
        $lowStockCount = Product::where('stock', '<', 10)->count();
        if ($lowStockCount > 0) {
            $notifications[] = [
                'type' => 'warning',
                'message' => "{$lowStockCount} produit(s) en stock faible",
                'url' => route('admin.products.index', ['stock' => 'low'])
            ];
        }

        // Out of stock alerts
        $outOfStockCount = Product::where('stock', '<=', 0)->count();
        if ($outOfStockCount > 0) {
            $notifications[] = [
                'type' => 'danger',
                'message' => "{$outOfStockCount} produit(s) en rupture de stock",
                'url' => route('admin.products.index', ['stock' => 'out'])
            ];
        }

        // Pending orders
        $pendingOrdersCount = Order::where('status', 'pending')->count();
        if ($pendingOrdersCount > 0) {
            $notifications[] = [
                'type' => 'info',
                'message' => "{$pendingOrdersCount} commande(s) en attente",
                'url' => route('admin.orders.index', ['status' => 'pending'])
            ];
        }

        // New users today
        $newUsersToday = User::where('role', 'client')
            ->whereDate('created_at', today())
            ->count();
        if ($newUsersToday > 0) {
            $notifications[] = [
                'type' => 'success',
                'message' => "{$newUsersToday} nouveau(x) utilisateur(s) aujourd'hui",
                'url' => route('admin.users.index', ['date_from' => today()->format('Y-m-d')])
            ];
        }

        return response()->json($notifications);
    }

    /**
     * Export dashboard data
     */
    public function export(Request $request)
    {
        $type = $request->get('type', 'overview');
        
        switch ($type) {
            case 'sales':
                return $this->exportSalesData();
            case 'orders':
                return $this->exportOrdersData();
            case 'products':
                return $this->exportProductsData();
            default:
                return $this->exportOverviewData();
        }
    }

    /**
     * Export overview data
     */
    private function exportOverviewData()
    {
        $data = [
            ['Métrique', 'Valeur'],
            ['Utilisateurs totaux', User::where('role', 'client')->count()],
            ['Produits totaux', Product::count()],
            ['Commandes totales', Order::count()],
            ['Revenus totaux', Order::where('status', 'completed')->sum('total_amount')],
            ['Commandes en attente', Order::where('status', 'pending')->count()],
            ['Produits en stock faible', Product::where('stock', '<', 10)->count()],
        ];

        return $this->generateCSVResponse($data, 'dashboard-overview');
    }

    /**
     * Generate CSV response
     */
    private function generateCSVResponse($data, $filename)
    {
        $csvData = '';
        foreach ($data as $row) {
            $csvData .= implode(',', $row) . "\n";
        }

        return response()->streamDownload(function() use ($csvData) {
            echo $csvData;
        }, $filename . '-' . date('Y-m-d') . '.csv');
    }
}