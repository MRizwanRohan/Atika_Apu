<?php

namespace App\Http\Controllers\Admin\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SmsTemplate;
use App\Services\Sms\SmsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    /**
     * Display a listing of the customers.
     *
     * @param Request $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('district', 'like', "%{$search}%")
                    ->orWhere('thana', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('area')) {
            $query->where('area', '=', $request->area);
        }
        if ($request->filled('district')) {
            $query->where('district', '=', $request->district);
        }
        if ($request->filled('thana')) {
            $query->where('thana', '=', $request->thana);
        }
        if ($request->filled('min_orders')) {
            $query->where('orders_count', '>=', $request->min_orders);
        }
        if ($request->filled('min_spent')) {
            $query->where('total_spent', '>=', $request->min_spent);
        }
        if ($request->filled('is_blocked')) {
            $query->where('is_blocked', '=', $request->is_blocked === 'blocked');
        }


        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $request->input('per_page', 10);
        $customers = $query->with(['smsLogs' => function($q) {
            $q->latest()->limit(1);
        }])->paginate($perPage)->withQueryString();

        // Transform to include last_sms_at
        $customers->getCollection()->transform(function($customer) {
            $customer->last_sms_at = $customer->smsLogs->first()?->created_at;
            $customer->last_sms_message = $customer->smsLogs->first()?->message;
            return $customer;
        });


        // Get unique districts and thanas for filtering
        $districts = Customer::whereNotNull('district')->where('district', '!=', '')->distinct()->orderBy('district')->pluck('district');
        $thanasQuery = Customer::whereNotNull('thana')->where('thana', '!=', '')->distinct()->orderBy('thana');
        if ($request->filled('district')) {
            $thanasQuery->where('district', $request->district);
        }
        $thanas = $thanasQuery->pluck('thana');

        // Get SMS templates for the bulk SMS modal
        $smsTemplates = SmsTemplate::orderBy('name')->get();

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
            'filters' => $request->all(),
            'districts' => $districts,
            'thanas' => $thanas,
            'smsTemplates' => $smsTemplates,
        ]);
    }

    /**
     * Send bulk SMS to selected customers
     */
    public function sendBulkSms(Request $request, SmsService $smsService)
    {
        $request->validate([
            'customer_ids' => 'required|array',
            'message' => 'required|string',
        ]);

        $customers = Customer::whereIn('id', $request->customer_ids)->get();
        $count = 0;
        $failed = 0;

        foreach ($customers as $customer) {
            // Replace dynamic values
            $message = str_replace(
                ['{name}', '{orders_count}', '{total_spent}', '{phone}'],
                [$customer->name, $customer->orders_count, $customer->total_spent, $customer->phone],
                $request->message
            );

            $result = $smsService->sendSms($customer->phone, $message);
            
            // Create log
            \App\Models\SmsLog::create([
                'customer_id' => $customer->id,
                'phone' => $customer->phone,
                'message' => $message,
                'status' => $result['status'] ? 'success' : 'failed',
                'error_message' => $result['status'] ? null : $result['message'],
            ]);

            if ($result['status']) {
                $count++;
            } else {
                $failed++;
            }
        }


        return back()->with('success', "SMS sending initiated. Success: {$count}, Failed: {$failed}.");
    }



    /**
     * Display a listing of SMS logs.
     */
    public function smsLogs(Request $request)
    {
        $query = \App\Models\SmsLog::with('customer')->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q->where('phone', 'like', "%{$search}%")
                ->orWhere('message', 'like', "%{$search}%")
            );
        }

        $logs = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Customers/SmsLogs', [
            'logs' => $logs,
            'filters' => $request->only(['search']),
        ]);
    }


    /**
     * Display the specified customer.

     *
     * @param Customer $customer
     * @return \Inertia\Response
     */
    public function show(Customer $customer)
    {
        $customer->load(['orders' => function($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        return Inertia::render('Admin/Customers/Show', [
            'customer' => $customer,
        ]);
    }

    /**
     * Store a newly created customer in storage (Manual Entry).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:customers,phone|max:255',
            'email' => 'nullable|email|unique:customers,email|max:255',
            'address' => 'nullable|string',
            'district' => 'nullable|string|max:255',
            'thana' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',
        ]);

        Customer::create($validated);

        return back()->with('success', 'Customer manually entered successfully.');
    }

    /**
     * Toggle the block status of the customer.
     */
    public function toggleBlock(Customer $customer)
    {
        $customer->update([
            'is_blocked' => !$customer->is_blocked
        ]);

        $status = $customer->is_blocked ? 'blocked' : 'unblocked';
        return back()->with('success', "Customer successfully {$status}.");
    }
}
