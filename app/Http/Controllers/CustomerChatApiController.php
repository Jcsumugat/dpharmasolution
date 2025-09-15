<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CustomerChat;
use App\Models\Conversation;
use App\Models\ChatMessage;
use App\Models\MessageAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CustomerChatApiController extends Controller
{
    public function findOrCreateConversation(Request $request): JsonResponse
    {
        Log::info('Customer findOrCreateConversation called', [
            'request_data' => $request->all(),
            'auth_customer' => Auth::guard('customer')->user()
        ]);

        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers_chat,customer_id',
            'type' => 'required|in:prescription_inquiry,order_concern,general_support,complaint,product_inquiry'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {
            // Verify customer owns this request
            $authCustomerId = Auth::guard('customer')->user()->customer_id;
            if ($authCustomerId != $request->customer_id) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'success' => false
                ], 403);
            }

            // Find existing active conversation
            $conversation = Conversation::where('customer_id', $request->customer_id)
                                      ->whereIn('status', ['active', 'pending'])
                                      ->first();

            if (!$conversation) {
                // Create new conversation
                $customer = CustomerChat::where('customer_id', $request->customer_id)->first();

                $conversation = Conversation::create([
                    'customer_id' => $request->customer_id,
                    'admin_id' => null,
                    'title' => "Chat with {$customer->full_name}",
                    'type' => $request->type,
                    'status' => 'active',
                    'priority' => 'normal',
                    'last_message_at' => now()
                ]);

                Log::info('New conversation created for customer', [
                    'conversation_id' => $conversation->id,
                    'customer_id' => $request->customer_id
                ]);
            }

            return response()->json([
                'conversation' => $conversation,
                'success' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to find/create conversation for customer', [
                'error' => $e->getMessage(),
                'customer_id' => $request->customer_id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to create conversation',
                'success' => false
            ], 500);
        }
    }

  public function sendMessage(Request $request, $conversationId): JsonResponse
{
    Log::info('Customer sendMessage called', [
        'conversation_id' => $conversationId,
        'request_data' => $request->all(),
        'files' => $request->hasFile('attachments') ? 'YES' : 'NO',
        'file_count' => $request->hasFile('attachments') ? count($request->file('attachments')) : 0,
        'message_empty' => empty($request->message),
        'message_value' => $request->message
    ]);

    try {
        // Custom validation logic
        $hasMessage = !empty($request->message);
        $hasFiles = $request->hasFile('attachments');

        if (!$hasMessage && !$hasFiles) {
            return response()->json([
                'errors' => ['message' => ['Either message or attachments are required']],
                'success' => false
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string|max:5000',
            'message_type' => 'nullable|string|in:text,file,image',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx'
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed for customer message', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        // Verify conversation exists and customer owns it
        $conversation = Conversation::findOrFail($conversationId);
        $customerId = Auth::guard('customer')->user()->customer_id;

        if ($conversation->customer_id != $customerId) {
            return response()->json([
                'error' => 'Unauthorized',
                'success' => false
            ], 403);
        }

        // Create message from customer
        $message = ChatMessage::create([
            'conversation_id' => $conversationId,
            'customer_id' => $customerId,
            'message' => $request->message ?: '', // Use empty string if null
            'message_type' => $request->message_type ?: ($hasFiles ? 'file' : 'text'),
            'is_from_customer' => true
        ]);

        Log::info('Message created successfully', [
            'message_id' => $message->id,
            'has_files' => $hasFiles
        ]);

        // Handle file attachments
        if ($hasFiles) {
            Log::info('Processing file attachments', [
                'file_count' => count($request->file('attachments'))
            ]);

            foreach ($request->file('attachments') as $index => $file) {
                try {
                    Log::info("Processing file {$index}", [
                        'original_name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType()
                    ]);

                    $path = $file->store('chat-attachments/' . date('Y/m'), 'public');

                    MessageAttachment::create([
                        'message_id' => $message->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_size' => $file->getSize(),
                        'file_type' => $file->getClientOriginalExtension(),
                        'mime_type' => $file->getMimeType()
                    ]);

                    Log::info("File {$index} processed successfully", ['path' => $path]);

                } catch (\Exception $e) {
                    Log::error("Failed to process file {$index}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        }

        // Update conversation
        $conversation->update(['last_message_at' => now()]);

        // Load message with relationships and format for response
        $message->load(['attachments']);

        $formattedMessage = [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'message' => $message->message,
            'message_type' => $message->message_type,
            'is_from_customer' => $message->is_from_customer,
            'has_attachments' => $message->attachments->count() > 0,
            'attachments' => $message->attachments,
            'created_at' => $message->created_at,
            'time_ago' => $message->created_at->diffForHumans()
        ];

        Log::info('Message sent successfully', [
            'message_id' => $message->id,
            'has_attachments' => $message->attachments->count() > 0
        ]);

        return response()->json([
            'message' => $formattedMessage,
            'success' => true
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to send message for customer', [
            'error' => $e->getMessage(),
            'conversation_id' => $conversationId,
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => 'Failed to send message',
            'success' => false
        ], 500);
    }
}

    public function getMessages(Request $request, $conversationId): JsonResponse
    {
        try {
            $conversation = Conversation::findOrFail($conversationId);

            // Verify customer owns this conversation
            $customerId = Auth::guard('customer')->user()->customer_id;
            if ($conversation->customer_id != $customerId) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'success' => false
                ], 403);
            }

            $messages = $conversation->messages()
                                    ->with(['attachments'])
                                    ->where('is_internal_note', false)
                                    ->orderBy('created_at')
                                    ->get();

            // Format messages for frontend
            $formattedMessages = $messages->map(function($message) {
                return [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'message' => $message->message,
                    'message_type' => $message->message_type,
                    'is_from_customer' => $message->is_from_customer,
                    'has_attachments' => $message->attachments->count() > 0,
                    'attachments' => $message->attachments,
                    'created_at' => $message->created_at,
                    'time_ago' => $message->created_at->diffForHumans()
                ];
            });

            return response()->json([
                'messages' => $formattedMessages,
                'success' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get messages for customer', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversationId
            ]);

            return response()->json([
                'error' => 'Failed to load messages',
                'success' => false
            ], 500);
        }
    }

    public function updateTypingStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers_chat,customer_id',
            'conversation_id' => 'required|exists:conversations,id',
            'is_typing' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Typing status updated'
        ]);
    }
    public function downloadAttachment($attachmentId)
{
    try {
        $attachment = MessageAttachment::findOrFail($attachmentId);

        // Verify the customer owns this attachment
        $message = ChatMessage::findOrFail($attachment->message_id);
        $customerId = Auth::guard('customer')->user()->customer_id;

        if ($message->customer_id != $customerId && $message->conversation->customer_id != $customerId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $filePath = storage_path('app/public/' . $attachment->file_path);

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return response()->download($filePath, $attachment->file_name);

    } catch (\Exception $e) {
        Log::error('Failed to download attachment', [
            'attachment_id' => $attachmentId,
            'error' => $e->getMessage()
        ]);

        return response()->json(['error' => 'Failed to download file'], 500);
    }
}
}
