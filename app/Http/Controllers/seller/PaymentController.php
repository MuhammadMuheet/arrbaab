<?php

namespace App\Http\Controllers\seller;
use App\Helpers\ActivityLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
class PaymentController extends Controller
{
    public function index(Request $request) {
        if (ActivityLogger::hasSellerPermission('payments', 'view')) {
            if ($request->ajax()) {
                $query = Transaction::query();
            
                if (!empty($request->current_date)) {
                    $query->whereDate('created_at', '=', $request->current_date);
                }

                if (!empty($request->start_date)) {
                    $query->whereDate('created_at', '>=', $request->start_date);
                }

                if (!empty($request->end_date)) {
                    $query->whereDate('created_at', '<=', $request->end_date);
                }

                $data = $query->where('user_id', Auth::user()->id)->orderBy('id', 'DESC')->get();
                $totalTransactions = 0;
                $totalAmountIn = 0;
                $totalAmountOut = 0;
                $totalSellerTransactions = 0;
                $totalCompanyTransactions = 0;
                $totalWallet = 0;
                foreach ($data as $item) {
                    $totalTransactions += $item->amount;
                    if($item->amount_type == 'in'){
                        $totalAmountIn += $item->amount;
                    }elseif($item->amount_type == 'out'){
                        $totalAmountOut += $item->amount;
                    }
                    if($item->user_type == 'seller'){
                        $totalSellerTransactions += $item->amount;
                    }elseif($item->user_type == 'logistic_company'){
                        $totalCompanyTransactions += $item->amount;
                    }
                    $totalWallet = @$totalAmountIn - @$totalAmountOut;
                }
                return Datatables::of($data)
                    ->addColumn('AmountType', function($data) {
                        $AmountTypeLabels = [
                            'in' => ['class' => 'bg-success', 'text' => 'Amount In'],
                            'out' => ['class' => 'bg-danger', 'text' => 'Amount Out'],
                        ];
                        if (isset($AmountTypeLabels[$data->amount_type])) {
                            return "<div class='badge {$AmountTypeLabels[$data->amount_type]['class']}'>{$AmountTypeLabels[$data->amount_type]['text']}</div>";
                        }
                        return "<div class='badge bg-secondary'>Unknown</div>";
                    })
                    ->addColumn('Amount', function($data) {
                     return $data->amount.' AED';
                    })
                    ->addColumn('Date', function($data) {
                        return Carbon::parse($data->created_at)->format('d/m/Y');
                       })
                    // ->addColumn('action', function($data) {
                    //         $action ='';
                    //     $action .=
                    //         '<a href="#" class=" edit btn btn-sm btn-dark" data-id="'.$data->id.'" data-bs-toggle="modal" data-bs-target="#edit_kt_modal_new_target">
                    //     <i style="font-size: 16px; padding: 0;" class="fa-solid fa-credit-card"></i>
                    // </a>';
                    //     return $action;
                    // })
                    ->with('totalTransactions', $totalTransactions)
                    ->with('totalWallet', $totalWallet)
                    ->with('totalAmountIn', $totalAmountIn)
                    ->with('totalAmountOut', $totalAmountOut)
                    ->rawColumns(['Date','AmountType','Amount'])
                    ->make(true);
            }
            ActivityLogger::UserLog('Open Seller Payments Page');
            return view('seller.pages.payments');
        }
    }
}
