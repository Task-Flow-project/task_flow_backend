<?php

namespace App\Domains\Workspace\Controllers;

use App\Domains\Activity\Actions\LogActivityAction;
use App\Domains\Invitation\Resources\InvitationResource;
use App\Domains\Membership\Resources\MemberResource;
use App\Domains\Notification\Actions\CreateNotificationAction;
use App\Domains\Subscription\Support\PlanLimits;
use App\Domains\Workspace\Actions\CreateWorkspaceAction;
use App\Domains\Workspace\Requests\AcceptInviteRequest;
use App\Domains\Workspace\Requests\InviteMemberRequest;
use App\Domains\Workspace\Requests\StoreWorkspaceRequest;
use App\Domains\Workspace\Requests\TransferWorkspaceRequest;
use App\Domains\Workspace\Requests\UpdateMemberRequest;
use App\Domains\Workspace\Requests\UpdateWorkspaceRequest;
use App\Domains\Workspace\Resources\WorkspaceResource;
use App\Exceptions\PlanLimitExceededException;
use App\Mail\WorkspaceInviteMail;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class WorkspaceController
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $workspaces = Workspace::whereHas('memberships', fn ($q) => $q->where('user_id', auth()->id()))->get();

        return response()->json(WorkspaceResource::collection($workspaces));
    }

    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        $user = $request->user();
        $limit = PlanLimits::workspacesAllowed($user);

        if ($limit !== null && $user->ownedWorkspaces()->count() >= $limit) {
            throw new PlanLimitExceededException("You've reached the Free plan limit of {$limit} workspace(s).");
        }

        $workspace = (new CreateWorkspaceAction())->execute($request->name, $user->id);

        return response()->json(new WorkspaceResource($workspace), 201);
    }

    public function show(Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $workspace->load('memberships.user');

        return response()->json(new WorkspaceResource($workspace));
    }

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('update', $workspace);

        $workspace->update($request->only('name'));

        return response()->json(new WorkspaceResource($workspace));
    }

    public function destroy(Workspace $workspace): JsonResponse
    {
        $this->authorize('delete', $workspace);

        $workspace->delete();

        return response()->json(null, 204);
    }

    public function members(Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $members = $workspace->memberships()->with('user')->get();

        return response()->json(MemberResource::collection($members));
    }

    public function updateMember(UpdateMemberRequest $request, Workspace $workspace, User $user): JsonResponse
    {
        $this->authorize('changeRole', $workspace);

        if ($workspace->owner_id === $user->id) {
            return response()->json(['message' => 'Use the transfer endpoint to change the workspace owner.'], 422);
        }

        $membership = $workspace->memberships()->where('user_id', $user->id)->firstOrFail();
        $membership->update(['role' => $request->role]);

        return response()->json(new MemberResource($membership->load('user')));
    }

    public function removeMember(Workspace $workspace, User $user): JsonResponse
    {
        $this->authorize('manageMembers', $workspace);

        if ($workspace->owner_id === $user->id) {
            return response()->json(['message' => 'The workspace owner cannot be removed. Transfer ownership first.'], 422);
        }

        $workspace->memberships()->where('user_id', $user->id)->delete();

        return response()->json(null, 204);
    }

    public function transfer(TransferWorkspaceRequest $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('transfer', $workspace);

        $newOwnerMembership = $workspace->memberships()->where('user_id', $request->userId)->first();

        if (! $newOwnerMembership) {
            return response()->json(['message' => 'The new owner must already be a member of this workspace.'], 422);
        }

        $workspace->memberships()->where('user_id', $workspace->owner_id)->update(['role' => 'admin']);
        $newOwnerMembership->update(['role' => 'owner']);
        $workspace->update(['owner_id' => $request->userId]);

        return response()->json(null, 204);
    }

    public function invite(InviteMemberRequest $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('manageMembers', $workspace);

        $limit = PlanLimits::membersAllowed($workspace);
        if ($limit !== null && $workspace->memberships()->count() >= $limit) {
            throw new PlanLimitExceededException("This workspace has reached the Free plan limit of {$limit} member(s).");
        }

        if ($workspace->memberships()->whereHas('user', fn ($q) => $q->where('email', $request->email))->exists()) {
            return response()->json(['message' => 'This user is already a member of the workspace.'], 422);
        }

        $invitation = Invitation::create([
            'workspace_id' => $workspace->id,
            'email' => $request->email,
            'role' => $request->role,
            'token' => Str::random(40),
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        Mail::to($request->email)->send(new WorkspaceInviteMail($invitation, $workspace->name, auth()->user()->name));

        $invitedUser = User::where('email', $request->email)->first();
        if ($invitedUser) {
            (new CreateNotificationAction())->execute($invitedUser, 'workspace.invited', [
                'workspace_id' => $workspace->id,
                'workspace_name' => $workspace->name,
                'invitation_id' => $invitation->id,
            ]);
        }

        return response()->json(new InvitationResource($invitation), 201);
    }

    public function invites(Workspace $workspace): JsonResponse
    {
        $this->authorize('manageMembers', $workspace);

        $invites = $workspace->invitations()->where('status', 'pending')->get();

        return response()->json(InvitationResource::collection($invites));
    }

    public function revokeInvite(Invitation $invitation): JsonResponse
    {
        $this->authorize('manageMembers', $invitation->workspace);

        $invitation->delete();

        return response()->json(null, 204);
    }

    public function acceptInvite(AcceptInviteRequest $request): JsonResponse
    {
        $invitation = Invitation::where('token', $request->token)->first();

        if (! $invitation) {
            return response()->json(['message' => 'This invitation does not exist.'], 404);
        }

        if ($invitation->status !== 'pending' || $invitation->expires_at->isPast()) {
            if ($invitation->status === 'pending') {
                $invitation->update(['status' => 'expired']);
            }

            return response()->json(['message' => 'This invitation is no longer valid.'], 422);
        }

        $user = $request->user();

        if (strcasecmp($invitation->email, $user->email) !== 0) {
            return response()->json(['message' => 'This invitation was sent to a different email address.'], 403);
        }

        $workspace = $invitation->workspace;

        if (! $workspace->memberships()->where('user_id', $user->id)->exists()) {
            $limit = PlanLimits::membersAllowed($workspace);
            if ($limit !== null && $workspace->memberships()->count() >= $limit) {
                throw new PlanLimitExceededException("This workspace has reached the Free plan limit of {$limit} member(s).");
            }

            Membership::create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'role' => $invitation->role,
            ]);

            (new LogActivityAction())->execute($user, 'workspace.joined', $workspace);
        }

        $invitation->update(['status' => 'accepted']);

        return response()->json(new WorkspaceResource($workspace->load('memberships.user')));
    }
}
