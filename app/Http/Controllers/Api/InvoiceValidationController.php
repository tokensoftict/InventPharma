<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InvoiceValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceValidationController extends Controller
{
    protected InvoiceValidationService $validationService;

    public function __construct(InvoiceValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    /**
     * Validate invoice items against stock availability, minimum quantity, pricing,
     * and customer purchase limits.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateInvoice(Request $request): JsonResponse
    {
        // Optional API Key / Tunnel Authentication
        $apiKey = config('services.invoice_api.key', env('INVOICE_API_KEY', env('CF_TUNNEL_API_KEY')));
        if (!empty($apiKey)) {
            $providedKey = $request->header('X-API-KEY') ?? $request->bearerToken();
            if ($providedKey !== $apiKey) {
                return response()->json([
                    'status' => false,
                    'valid' => false,
                    'message' => 'Unauthorized: Invalid or missing API key.',
                ], 401);
            }
        }

        $validator = Validator::make($request->all(), [
            'department' => 'required|string',
            'customer_id' => 'nullable',
            'items' => 'required|array|min:1',
            'items.*.stock_id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.selectedOptions' => 'nullable|array',
            'check_purchase_limit' => 'nullable|boolean',
        ], [
            'department.required' => 'The department field is required (e.g. wholesales, bulksales, retail).',
            'items.required' => 'At least one invoice item is required.',
            'items.*.stock_id.required' => 'Each item must specify a valid stock_id.',
            'items.*.quantity.required' => 'Each item must specify a quantity greater than zero.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'valid' => false,
                'message' => 'The request payload failed structural validation.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $department = strtolower(trim((string) $request->input('department')));
        $items = (array) $request->input('items', []);
        $customerId = $request->input('customer_id');
        $checkPurchaseLimit = $request->boolean('check_purchase_limit', true);

        // Run validation through the extracted service
        $result = $this->validationService->validateOnlineInvoice(
            items: $items,
            department: $department,
            customerId: $customerId,
            checkPurchaseLimits: $checkPurchaseLimit
        );

        if (!$result['status']) {
            return response()->json([
                'status' => false,
                'valid' => false,
                'message' => 'Validation failed for one or more invoice items.',
                'errors' => $result['errors'],
                'error_messages' => array_values($result['errors']),
                'data' => $result['data'],
            ], 422);
        }

        return response()->json([
            'status' => true,
            'valid' => true,
            'message' => 'Invoice items and purchase limits validated successfully.',
            'errors' => [],
            'data' => $result['data'],
        ], 200);
    }

    /**
     * Dedicated endpoint to validate customer purchase limits only.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validatePurchaseLimits(Request $request): JsonResponse
    {
        // Optional API Key / Tunnel Authentication
        $apiKey = config('services.invoice_api.key', env('INVOICE_API_KEY', env('CF_TUNNEL_API_KEY')));
        if (!empty($apiKey)) {
            $providedKey = $request->header('X-API-KEY') ?? $request->bearerToken();
            if ($providedKey !== $apiKey) {
                return response()->json([
                    'status' => false,
                    'valid' => false,
                    'message' => 'Unauthorized: Invalid or missing API key.',
                ], 401);
            }
        }

        $validator = Validator::make($request->all(), [
            'department' => 'required|string',
            'customer_id' => 'required|integer|min:1',
            'items' => 'required|array|min:1',
            'items.*.stock_id' => 'required|integer|min:1',
            'items.*.quantity' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'valid' => false,
                'message' => 'The request payload failed structural validation.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $department = strtolower(trim((string) $request->input('department')));
        $items = (array) $request->input('items', []);
        $customerId = (int) $request->input('customer_id');

        $result = $this->validationService->validatePurchaseLimits($customerId, $items, $department);

        if (!$result['status']) {
            return response()->json([
                'status' => false,
                'valid' => false,
                'message' => 'Purchase limit validation failed.',
                'errors' => $result['errors'],
                'error_messages' => array_values($result['errors']),
            ], 422);
        }

        return response()->json([
            'status' => true,
            'valid' => true,
            'message' => 'Customer purchase limits validated successfully.',
            'errors' => [],
        ], 200);
    }
}
