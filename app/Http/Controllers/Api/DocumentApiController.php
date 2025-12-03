<?php

namespace App\Http\Controllers\Api;

use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DocumentApiController extends Controller
{
     public function __construct(
        protected DocumentService $documentService
    ) {}

    /**
     * Get documents accessible by the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = Document::query();
        
        // Filter based on user role
        switch ($user->role->name) {
            case 'insurer':
                $query->where('insurance_provider_id', $user->userProfile->insurance_provider_id ?? null);
                break;
            case 'supplier':
                $query->where('supplier_id', $user->userProfile->supplier_id ?? null);
                break;
            case 'physician':
                $query->whereHas('prescription', function ($q) use ($user) {
                    $q->where('physician_id', $user->id);
                });
                break;
        }
        
        // Apply filters
        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }
        
        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }
        
        if ($request->filled('prescription_id')) {
            $query->where('prescription_id', $request->prescription_id);
        }
        
        if ($request->filled('order_id')) {
            $query->where('order_id', $request->order_id);
        }
        
        if ($request->filled('insurance_claim_id')) {
            $query->where('insurance_claim_id', $request->insurance_claim_id);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'uploaded_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Pagination
        $perPage = $request->get('per_page', 15);
        $documents = $query->with([
            'category',
            'uploadedBy:id,name,email',
            'verifiedBy:id,name,email',
            'prescription:id,prescription_number',
            'order:id,order_number',
            'insuranceClaim:id,claim_number',
        ])->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $documents,
        ]);
    }

    /**
     * Get a specific document
     */
    public function show(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();
        
        // Check access
        if (!$document->hasAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this document',
            ], 403);
        }
        
        // Log access
        $document->logAccess($user, 'view');
        
        $document->load([
            'category',
            'uploadedBy:id,name,email',
            'verifiedBy:id,name,email',
            'prescription',
            'order',
            'insuranceClaim',
            'supplier',
            'insuranceProvider',
            'patient',
        ]);
        
        return response()->json([
            'success' => true,
            'data' => $document,
        ]);
    }

    /**
     * Upload a new document
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'category_id' => 'required|exists:document_categories,id',
            'document_type' => 'required|string|in:claim_form,prescription,invoice,receipt,delivery_note,credit_note,purchase_order,payment_voucher,other',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prescription_id' => 'nullable|exists:prescriptions,id',
            'order_id' => 'nullable|exists:orders,id',
            'insurance_claim_id' => 'nullable|exists:insurance_claims,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'insurance_provider_id' => 'nullable|exists:insurance_providers,id',
            'patient_id' => 'nullable|exists:patients,id',
            'tags' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        try {
            $document = $this->documentService->uploadDocument(
                $request->file('file'),
                $request->user(),
                $validator->validated()
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'data' => $document,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a document
     */
    public function download(Request $request, Document $document)
    {
        $user = $request->user();
        
        // Check access
        if (!$document->hasAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to download this document',
            ], 403);
        }
        
        // Check if file exists
        if (!Storage::exists($document->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Document file not found',
            ], 404);
        }
        
        // Log access
        $document->logAccess($user, 'download');
        
        return Storage::download($document->file_path, $document->file_name);
    }

    /**
     * Get documents for a specific claim
     */
    public function getClaimDocuments(Request $request, int $claimId): JsonResponse
    {
        $user = $request->user();
        
        $documents = Document::where('insurance_claim_id', $claimId)
            ->with(['category', 'uploadedBy:id,name,email'])
            ->get();
        
        // Filter documents user has access to
        $accessibleDocuments = $documents->filter(function ($document) use ($user) {
            return $document->hasAccess($user);
        });
        
        return response()->json([
            'success' => true,
            'data' => $accessibleDocuments->values(),
        ]);
    }

    /**
     * Get documents for a specific order
     */
    public function getOrderDocuments(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user();
        
        $documents = Document::where('order_id', $orderId)
            ->with(['category', 'uploadedBy:id,name,email'])
            ->get();
        
        // Filter documents user has access to
        $accessibleDocuments = $documents->filter(function ($document) use ($user) {
            return $document->hasAccess($user);
        });
        
        return response()->json([
            'success' => true,
            'data' => $accessibleDocuments->values(),
        ]);
    }

    /**
     * Get documents for a specific prescription
     */
    public function getPrescriptionDocuments(Request $request, int $prescriptionId): JsonResponse
    {
        $user = $request->user();
        
        $documents = Document::where('prescription_id', $prescriptionId)
            ->with(['category', 'uploadedBy:id,name,email'])
            ->get();
        
        // Filter documents user has access to
        $accessibleDocuments = $documents->filter(function ($document) use ($user) {
            return $document->hasAccess($user);
        });
        
        return response()->json([
            'success' => true,
            'data' => $accessibleDocuments->values(),
        ]);
    }

    /**
     * Verify a document (insurers only)
     */
    public function verify(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();
        
        // Check if user is an insurer or admin
        if (!in_array($user->role->name, ['insurer', 'admin', 'operations'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only insurers and admins can verify documents',
            ], 403);
        }
        
        // Check access
        if (!$document->hasAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to verify this document',
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $document->verify($user, $request->input('notes'));
        
        return response()->json([
            'success' => true,
            'message' => 'Document verified successfully',
            'data' => $document->fresh(),
        ]);
    }

    /**
     * Reject a document (insurers only)
     */
    public function reject(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();
        
        // Check if user is an insurer or admin
        if (!in_array($user->role->name, ['insurer', 'admin', 'operations'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only insurers and admins can reject documents',
            ], 403);
        }
        
        // Check access
        if (!$document->hasAccess($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to reject this document',
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'notes' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        
        $document->reject($user, $request->input('notes'));
        
        return response()->json([
            'success' => true,
            'message' => 'Document rejected',
            'data' => $document->fresh(),
        ]);
    }

    /**
     * Get document statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->documentService->getStatistics();
        
        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
