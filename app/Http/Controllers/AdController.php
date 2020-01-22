<?php
    namespace App\Http\Controllers;

    use App\Http\Requests\ { AdStore, AdUpdate };
    use Illuminate\Support\Str;
    use Illuminate\Http\Request;
    use App\Repositories\AdRepository;
    use Carbon\Carbon;
    use App\Models\ { Category, Region, Ad, Upload };



    class AdController extends Controller
    {
        /**
         * Display a listing of the resource.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  String  $regionSlug
         * @param  Integer  $departementCode
         * @param  Integer  $communeCode
         * @return \Illuminate\Http\Response
         */
        public function index(
            Request $request,
            $regionSlug = null,
            $departementCode = null,
            $communeCode = null
        ) {
            $categories = Category::select('name', 'id')->oldest('name')->get();
            $regions = Region::select('id', 'code', 'name', 'slug')->oldest('name')->get();
            $region = $regionSlug ? Region::whereSlug($regionSlug)->firstOrFail() : null;
            $page = $request->query('page', 0);
            return view('adsvue', compact('categories', 'regions', 'region', 'departementCode', 'communeCode', 'page'));
        }

        /**
     * Ad repository.
     *
     * @var App\Repositories\AdRepository
     */
        protected $adRepository;
        /**
         * Create a new controller instance.
         *
         * @return void
         */
        public function __construct(AdRepository $adRepository)
        {
            $this->adRepository = $adRepository;
        }
        /**
         * Search ads.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  String  $slug
         * @return \Illuminate\Http\Response
         */
        public function search(Request $request)
        {
            setlocale(LC_TIME, 'fr_FR');
            $ads = $this->adRepository->search($request);
            return view('partials.ads', compact('ads'));

        }
        public function show(Ad $ad)
        {

            $this->authorize('show', $ad);
            $photos = $this->adRepository->getPhotos($ad);
            return view('ad', compact('ad', 'photos'));
        }

        public function create(Request $request)
{
    if(!$request->session()->has('index')) {
        $request->session()->put('index', Str::random(30));
    }
    $categories = Category::select('name', 'id')->oldest('name')->get();
    $regions = Region::oldest('name')->get();
    return view('create', compact('categories', 'regions'));
}

public function store(AdStore $request)
{
    $commune = json_decode(file_get_contents('https://geo.api.gouv.fr/communes/' . $request->commune), true);
    $ad = $this->adRepository->create([
        'title' => $request->title,
        'texte' => $request->texte,
        'category_id' => $request->category,
        'region_id' => $request->region,
        'departement' => $request->departement,
        'commune' => $request->commune,
        'commune_name' => $commune['nom'],
        'commune_postal' => $commune['codesPostaux'][0],
        'user_id' => auth()->check() ? auth()->id() : 0,
        'pseudo' => auth()->check() ? auth()->user()->name :$request->pseudo,
        'email' => auth()->check() ? auth()->user()->email : $request->email,
        'limit' => Carbon::now()->addWeeks($request->limit),
    ]);
    if($request->session()->has('index')) {
        $index = $request->session()->get('index');
        Upload::whereIndex($index)->update(['ad_id' => $ad->id, 'index' => 0]);
    }
    return view('adconfirm');
}

public function edit(Ad $ad)
{
    $this->authorize('manage', $ad);
    return view('edit', compact('ad'));
}
public function update(AdUpdate $request, Ad $ad)
{
    $this->authorize('manage', $ad);
    $this->adRepository->update($ad, $request->all());
    $request->session()->flash('status', "L'annonce a bien été modifiée.");
    return back();
}
        
    }
