<?php

namespace App\Http\Controllers;

use App\Notifications\ { AdApprove, AdRefuse, MessageApprove };
use App\Models\ { Ad, Message };
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Repositories\ { AdRepository, MessageRepository };
use App\Http\Requests\MessageRefuse as MessageRefuseRequest;

class AdminController extends Controller
{
    protected $adRepository;
    protected $messagerepository;
    public function __construct(AdRepository $adRepository, Messagerepository $messagerepository)
    {
        $this->adRepository = $adRepository;
        $this->messagerepository = $messagerepository;
    }
    public function index()
    {
        $adModerationCount = $this->adRepository->noActiveCount();
        $adPerimesCount = $this->adRepository->obsoleteCount();
        $messageModerationCount = $this->messagerepository->count();
        return view('admin.index', compact('adModerationCount', 'messageModerationCount', 'adPerimesCount'));
    }

    public function ads()
    {
        $adModeration = $this->adRepository->noActive(5);
        return view('admin.ads', compact('adModeration'));
    }

    public function approve(Request $request, Ad $ad)
{
    $this->adRepository->approve($ad);
    $request->session()->flash('status', "L'annonce a bien été approuvée et le rédacteur va être notifié.");
    $ad->notify(new AdApprove($ad));
    return response()->json(['id' => $ad->id]);
}

public function refuse(MessageRefuseRequest $request)
{
    $ad = $this->adRepository->getById($request->id);
    $ad->notify(new AdRefuse($request->message));
    $this->adRepository->delete($ad);
    $request->session()->flash('status', "L'annonce a bien été refusée et le rédacteur va être notifié.");
    return response()->json(['id' => $ad->id]);
}

public function obsoletes()
{
    $ads = $this->adRepository->obsolete(5);
    return view('admin.obsoletes', compact('ads'));
}

public function addWeek(Request $request, Ad $ad)
{
    $this->authorize('manage', $ad);
    $limit = $this->adRepository->addWeek($ad);
    return response()->json([
        'limit' => $limit->format('d-m-Y'),
        'ok' => $limit->greaterThan(Carbon::now()),
    ]);
}

public function destroy(Request $request, Ad $ad)
{
    $this->authorize('manage', $ad);
    $this->adRepository->delete($ad);
    $request->session()->flash('status', "L'annonce a bien été supprimée.");
    return response()->json();
}

public function messages()
{
    $messages = $this->messagerepository->all(5);
    return view('admin.messages', compact('messages'));
}

public function messageApprove(Request $request, Message $message)
{
    $ad = $this->messagerepository->getAd($message);
    $ad->notify(new MessageApprove($ad, $message));
    $this->messagerepository->delete($message);
    $request->session()->flash('status', "Le message a bien été approuvé et le rédacteur va être notifié.");
    return response()->json(['id' => $message->id]);
}

public function MessageRefuse(MessageRefuseRequest $request)
{
    $message = $this->messagerepository->getById($request->id);
    $ad = $this->messagerepository->getAd($message);
    $ad->notify(new MessageRefuse($ad, $message, $request->message));
    $this->messagerepository->delete($message);
    $request->session()->flash('status', "Le message a bien été refusé et le rédacteur va être notifié.");
    return response()->json(['id' => $ad->id]);
}

}
