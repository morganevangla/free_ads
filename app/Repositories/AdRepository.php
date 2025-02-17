<?php
    namespace App\Repositories;
    use App\Models\Ad;
    use Carbon\Carbon;
    class AdRepository
    {
        /**
         * Search.
         *
         * @param \Illuminate\Http\Request $request
         */
        public function search($request)
        {
            $ads = Ad::query();
            if($request->region != 0) {
                $ads = Ad::whereHas('region', function ($query) use ($request) {
                    $query->where('regions.id', $request->region);
                })->when($request->departement != 0, function ($query) use ($request) {
                    return $query->where('departement', $request->departement);
                })->when($request->commune != 0, function ($query) use ($request) {
                    return $query->where('commune', $request->commune);
                });
            }
            if($request->category != 0) {
                $ads->whereHas('category', function ($query) use ($request) {
                    $query->where('categories.id', $request->category);
                });
            }
            return $ads->with('category', 'photos')
                ->whereActive(true)
                ->latest()
                ->paginate(3);
        }

        public function getPhotos($ad)
        {
            return $ad->photos()->get();
        }

        public function getById($id)
        {
            return Ad::findOrFail($id);
        }

        public function create($data)
        {
            return Ad::create($data);
        }

        public function noActiveCount($ads = null)
        {
            if($ads) {
                return $ads->where('active', false)->count();
            }
            return Ad::where('active', false)->count();
        }
        public function obsoleteCount($ads = null)
        {
            if($ads) {
                return $ads->where('active', true)->where('limit', '<', Carbon::now())->count();
            }
            return Ad::where('limit', '<', Carbon::now())->count();
        }

        public function noActive($nbr)
        {
            return Ad::whereActive(false)->latest()->paginate($nbr);
        }

        public function approve($ad)
        {
            $ad->active = true;
            $ad->save();
        }

        public function delete($ad)
        {
            $ad->delete();
        }

        public function obsolete($nbr)
        {
            return Ad::where('limit', '<', Carbon::now())->latest('limit')->paginate($nbr);
        }
        public function addWeek($ad)
        {
            $limit = Carbon::create($ad->limit);
            $limit->addWeek();
            $ad->limit = $limit;
            $ad->save();
            return $limit;
        }

        public function activeCount($ads)
        {
            return $ads->where('active', true)->where('limit', '>=', Carbon::now())->count();
        }
        public function getByUser($user)
        {
            return $user->ads()->get();
        }

        public function active($user, $nbr)
        {
            return $user->ads()->whereActive(true)->where('limit', '>=', Carbon::now())->paginate($nbr);
        }

        public function update($ad, $data)
        {
            $ad->update($data);
        }

        public function attente($user, $nbr)
        {
            return $user->ads()->whereActive(false)->paginate($nbr);
        }

        public function obsoleteForUser($user, $nbr)
        {
            return $user->ads()->where('limit', '<', Carbon::now())->latest('limit')->paginate($nbr);
        }
    }