<?php

declare(strict_types=1);

namespace App\Domains\Chat\Http\Controllers;

use App\Domains\Chat\Actions\StartConversation;
use App\Domains\Identity\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class StartConversationController
{
    public function __invoke(Request $request, StartConversation $startConversation): RedirectResponse
    {
        $validated = $request->validate([
            'context_type' => 'required|string|in:listing,job',
            'context_id' => 'required|string|uuid',
        ]);

        /** @var User $user */
        $user = Auth::user();

        $conversation = $startConversation->handle(
            initiator: $user,
            contextType: $validated['context_type'],
            contextId: $validated['context_id'],
        );

        return redirect()->route('conversations.show', $conversation);
    }
}
