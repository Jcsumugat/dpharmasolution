<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CustomerChat;
use App\Models\Conversation;
use App\Models\ChatMessage;
use App\Models\MessageAttachment;
use App\Models\ConversationParticipant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ChatConversationController extends Controller
{
    public function getConversations(Request $request): JsonResponse
    {
        $query = Conversation::with(['customer', 'latestMessage', 'admin'])
            ->withCount('messages');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }

        $conversations = $query->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate(50);

        return response()->json([
            'conversations' => $conversations,
            'success' => true
        ]);
    }

public function sendMessage(Request $request, $conversationId): JsonResponse
{
    // Start comprehensive logging
    \Log::info('=== SEND MESSAGE DEBUG START ===', [
        'conversation_id' => $conversationId,
        'request_data' => $request->all(),
        'auth_user_id' => Auth::id(),
        'auth_user' => Auth::user(),
        'request_method' => $request->method(),
        'request_url' => $request->fullUrl(),
        'headers' => $request->headers->all()
    ]);

    try {
        // Validate conversation exists
        \Log::info('Checking if conversation exists', ['conversation_id' => $conversationId]);

        $conversation = Conversation::find($conversationId);
        if (!$conversation) {
            \Log::error('Conversation not found', ['conversation_id' => $conversationId]);
            return response()->json([
                'error' => 'Conversation not found',
                'success' => false
            ], 404);
        }

        \Log::info('Conversation found', [
            'conversation' => $conversation->toArray(),
            'customer_id' => $conversation->customer_id
        ]);

        // Validate request data
        \Log::info('Starting validation');

        $validator = Validator::make($request->all(), [
            'message' => 'required_without:attachments|string|max:5000',
            'message_type' => 'nullable|string|in:text,file,image,system',
            'is_internal_note' => 'nullable|in:true,false,1,0',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx'
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        \Log::info('Validation passed');

        // Prepare message data
        $messageData = [
            'conversation_id' => $conversationId,
            'admin_id' => Auth::id(),
            'message' => $request->message ?? '',
            'message_type' => $request->message_type ?? 'text',
            'is_from_customer' => false,
            'is_internal_note' => $request->boolean('is_internal_note', false)
        ];

        \Log::info('Prepared message data', ['message_data' => $messageData]);

        // Check database connection
        try {
            \DB::connection()->getPdo();
            \Log::info('Database connection verified');
        } catch (\Exception $e) {
            \Log::error('Database connection failed', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Database connection failed',
                'success' => false
            ], 500);
        }

        // Check if ChatMessage table exists and has required columns
        try {
            $tableSchema = \Schema::getColumnListing('chat_messages');
            \Log::info('ChatMessage table schema', ['columns' => $tableSchema]);

            $requiredColumns = ['conversation_id', 'admin_id', 'message', 'message_type', 'is_from_customer', 'is_internal_note'];
            $missingColumns = array_diff($requiredColumns, $tableSchema);

            if (!empty($missingColumns)) {
                \Log::error('Missing columns in chat_messages table', ['missing' => $missingColumns]);
                return response()->json([
                    'error' => 'Missing database columns: ' . implode(', ', $missingColumns),
                    'success' => false
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error('Failed to check table schema', ['error' => $e->getMessage()]);
        }

        // Create message
        \Log::info('Attempting to create message');

        $message = ChatMessage::create($messageData);

        if (!$message) {
            \Log::error('Failed to create message - returned null/false');
            return response()->json([
                'error' => 'Failed to create message',
                'success' => false
            ], 500);
        }

        \Log::info('Message created successfully', [
            'message_id' => $message->id,
            'message_data' => $message->toArray()
        ]);

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            \Log::info('Processing attachments', [
                'file_count' => count($request->file('attachments'))
            ]);

            foreach ($request->file('attachments') as $index => $file) {
                try {
                    \Log::info("Processing attachment {$index}", [
                        'original_name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType()
                    ]);

                    $path = $file->store('chat-attachments/' . date('Y/m'), 'public');

                    $attachment = MessageAttachment::create([
                        'message_id' => $message->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_size' => $file->getSize(),
                        'file_type' => $file->getClientOriginalExtension(),
                        'mime_type' => $file->getMimeType()
                    ]);

                    \Log::info("Attachment {$index} created", [
                        'attachment_id' => $attachment->id,
                        'path' => $path
                    ]);

                } catch (\Exception $e) {
                    \Log::error("Failed to process attachment {$index}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        }

        // Update conversation
        \Log::info('Updating conversation');

        try {
            $conversation->update([
                'last_message_at' => now(),
                'status' => 'active'
            ]);
            \Log::info('Conversation updated successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to update conversation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Load relationships
        \Log::info('Loading message relationships');

        try {
            $message->load(['attachments', 'admin']);
            \Log::info('Relationships loaded successfully', [
                'attachments_count' => $message->attachments->count(),
                'admin_loaded' => $message->admin ? true : false
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to load relationships', [
                'error' => $e->getMessage()
            ]);
        }

        // Format response
        $formattedMessage = [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'message' => $message->message,
            'message_type' => $message->message_type,
            'is_from_customer' => $message->is_from_customer,
            'is_internal_note' => $message->is_internal_note,
            'has_attachments' => $message->attachments->count() > 0,
            'attachments' => $message->attachments,
            'created_at' => $message->created_at,
            'time_ago' => $message->created_at->diffForHumans(),
            'admin' => $message->admin ? [
                'id' => $message->admin->id,
                'name' => $message->admin->name ?? 'Admin'
            ] : null
        ];

        \Log::info('Response formatted successfully', [
            'formatted_message' => $formattedMessage
        ]);

        \Log::info('=== SEND MESSAGE DEBUG SUCCESS ===');

        return response()->json([
            'message' => $formattedMessage,
            'success' => true
        ]);

    } catch (\Exception $e) {
        \Log::error('=== SEND MESSAGE DEBUG ERROR ===', [
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'error_trace' => $e->getTraceAsString(),
            'request_data' => $request->all(),
            'conversation_id' => $conversationId
        ]);

        return response()->json([
            'error' => 'Server error: ' . $e->getMessage(),
            'success' => false,
            'debug_info' => [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode()
            ]
        ], 500);
    }
}

public function findOrCreateConversation(Request $request): JsonResponse
{
    \Log::info('=== FIND OR CREATE CONVERSATION DEBUG START ===', [
        'request_data' => $request->all(),
        'auth_user_id' => Auth::id()
    ]);

    try {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers_chat,customer_id',
            'type' => 'required|in:prescription_inquiry,order_concern,general_support,complaint,product_inquiry',
            'priority' => 'nullable|in:normal,high,urgent'
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed in findOrCreateConversation', [
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json([
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        // Check if customer exists
        $customer = CustomerChat::where('customer_id', $request->customer_id)->first();
        if (!$customer) {
            \Log::error('Customer not found in customers_chat table', [
                'customer_id' => $request->customer_id
            ]);
            return response()->json([
                'error' => 'Customer not found',
                'success' => false
            ], 404);
        }

        \Log::info('Customer found', ['customer' => $customer->toArray()]);

        // Find existing active conversation
        $conversation = Conversation::where('customer_id', $request->customer_id)
                                  ->whereIn('status', ['active', 'pending'])
                                  ->first();

        if ($conversation) {
            \Log::info('Existing conversation found', [
                'conversation_id' => $conversation->id,
                'status' => $conversation->status
            ]);
        } else {
            \Log::info('Creating new conversation');

            $conversationData = [
                'customer_id' => $request->customer_id,
                'admin_id' => Auth::id(),
                'title' => $request->title ?? "Chat with {$customer->full_name}",
                'type' => $request->type,
                'status' => 'active',
                'priority' => $request->priority ?? 'normal',
                'last_message_at' => now()
            ];

            \Log::info('Conversation data prepared', ['data' => $conversationData]);

            $conversation = Conversation::create($conversationData);

            \Log::info('New conversation created', [
                'conversation_id' => $conversation->id,
                'conversation_data' => $conversation->toArray()
            ]);

            // Create participants if tables exist
            try {
                if (\Schema::hasTable('conversation_participants')) {
                    ConversationParticipant::create([
                        'conversation_id' => $conversation->id,
                        'customer_id' => $request->customer_id,
                        'joined_at' => now()
                    ]);

                    ConversationParticipant::create([
                        'conversation_id' => $conversation->id,
                        'admin_id' => Auth::id(),
                        'joined_at' => now()
                    ]);

                    \Log::info('Conversation participants created');
                } else {
                    \Log::warning('conversation_participants table does not exist');
                }
            } catch (\Exception $e) {
                \Log::error('Failed to create conversation participants', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        $conversation->load(['customer', 'admin']);

        \Log::info('=== FIND OR CREATE CONVERSATION DEBUG SUCCESS ===', [
            'conversation_id' => $conversation->id
        ]);

        return response()->json([
            'conversation' => $conversation,
            'success' => true
        ]);

    } catch (\Exception $e) {
        \Log::error('=== FIND OR CREATE CONVERSATION DEBUG ERROR ===', [
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'error_trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => 'Server error: ' . $e->getMessage(),
            'success' => false
        ], 500);
    }
}

    public function getMessages(Request $request, $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);

        $messages = $conversation->messages()
            ->with(['attachments', 'customer', 'admin'])
            ->orderBy('created_at')
            ->get();

        // Format messages for frontend
        $formattedMessages = $messages->map(function ($message) {
            return [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'message' => $message->message,
                'message_type' => $message->message_type,
                'is_from_customer' => $message->is_from_customer,
                'is_internal_note' => $message->is_internal_note,
                'has_attachments' => $message->attachments->count() > 0,
                'attachments' => $message->attachments,
                'created_at' => $message->created_at,
                'time_ago' => $message->created_at->diffForHumans(),
                'customer' => $message->customer ? [
                    'id' => $message->customer->customer_id,
                    'name' => $message->customer->full_name
                ] : null,
                'admin' => $message->admin ? [
                    'id' => $message->admin->id,
                    'name' => $message->admin->name ?? 'Admin'
                ] : null
            ];
        });

        // Mark messages as read for admin
        if (method_exists($conversation, 'markAsRead')) {
            $conversation->markAsRead(Auth::id(), true);
        }

        return response()->json([
            'messages' => $formattedMessages,
            'conversation' => $conversation,
            'success' => true
        ]);
    }

    public function updateStatus(Request $request, $conversationId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,resolved,closed,pending'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        $conversation = Conversation::findOrFail($conversationId);
        $conversation->update(['status' => $request->status]);

        // Create system message
        ChatMessage::create([
            'conversation_id' => $conversationId,
            'admin_id' => Auth::id(),
            'message' => "Conversation marked as {$request->status}",
            'message_type' => 'system',
            'is_from_customer' => false
        ]);

        return response()->json([
            'conversation' => $conversation,
            'success' => true
        ]);
    }

    public function markAsRead(Request $request, $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $conversation->markAsRead(Auth::id(), true);

        return response()->json([
            'success' => true,
            'message' => 'Conversation marked as read'
        ]);
    }

    public function getStats(): JsonResponse
    {
        $stats = [
            'active' => Conversation::active()->count(),
            'urgent' => Conversation::where('priority', 'urgent')->count(),
            'unassigned' => Conversation::whereNull('admin_id')->count(),
            'resolved_today' => Conversation::where('status', 'resolved')
                ->whereDate('updated_at', today())
                ->count(),
            'total_messages_today' => ChatMessage::whereDate('created_at', today())->count(),
        ];

        return response()->json([
            'stats' => $stats,
            'success' => true
        ]);
    }
}
