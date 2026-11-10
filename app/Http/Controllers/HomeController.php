<?php

namespace App\Http\Controllers;

use App\Models\Slide;
use App\Models\Tour;
use App\Models\TourCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HomeController extends Controller
{
    public function index()
    {
        return view('site.home', [
            'slides' => Slide::active()->get(),
            'categories' => TourCategory::active()->get(),
            'tours' => $this->tourQuery()->where('is_featured', true)->get(),
        ]);
    }

    public function tours(Request $request)
    {
        $categories = TourCategory::active()->get();
        $active = $categories->firstWhere('slug', $request->query('kategori'));

        return view('site.tours', [
            'categories' => $categories,
            'activeCategory' => $active,
            'tours' => $this->tourQuery()
                ->when($active, fn ($q) => $q->where('tour_category_id', $active->id))
                ->get(),
        ]);
    }

    public function show(Tour $tour)
    {
        abort_unless($tour->status === 'published', 404);
        $tour->load('category')->loadCount('activeBookings');

        return view('site.tour', [
            'tour' => $tour,
            'related' => $this->tourQuery()
                ->whereKeyNot($tour->id)
                ->where('tour_category_id', $tour->tour_category_id)
                ->limit(3)->get(),
        ]);
    }

    public function sitemap(): Response
    {
        $tours = Tour::published()->get(['slug', 'updated_at']);

        return response()->view('site.sitemap', compact('tours'))->header('Content-Type', 'application/xml');
    }

    private function tourQuery()
    {
        return Tour::published()->with('category')->withCount('activeBookings')->ordered();
    }
}
