<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PropertyRequest;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $properties = Property::withCount('investmentProjects')
            ->when($request->query('type'), fn ($q, $t) => $q->where('type', $t))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.properties', ['properties' => $properties, 'property' => null]);
    }

    public function store(PropertyRequest $request): RedirectResponse
    {
        Property::create($request->validated() + ['created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);

        return redirect()->route('admin.properties.index')->with('success', 'Property created.');
    }

    public function show(Property $property)
    {
        return view('admin.show_property', [
            'property' => $property,
            'projects' => $property->investmentProjects()->withCount('investments')->get(),
            'loanProducts' => $property->loanProjects()->get(),
        ]);
    }

    public function edit(Property $property)
    {
        return view('admin.properties', ['properties' => Property::latest()->paginate(15), 'property' => $property]);
    }

    public function update(PropertyRequest $request, Property $property): RedirectResponse
    {
        $property->update($request->validated() + ['updated_by' => $request->user()->id]);

        return redirect()->route('admin.properties.index')->with('success', 'Property updated.');
    }
}
