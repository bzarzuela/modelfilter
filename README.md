# Model Filter

Utility Class for Laravel 5 to assist in easily filtering paginated results

# Installation

composer require bzarzuela/modelfilter

# Usage

Use in actions that show the list of models. In this example, it's the index action in the TicketsController.

	public function index()
	{
	    $model_filter = new ModelFilter('tickets');

	    $model_filter->setRules([
	        'id' => ['primary'],
	        'concern_types' => ['in', 'concern_type_id'],
	        'created_from' => ['from', 'created_at'],
	        'created_to' => ['to', 'created_at'],
	    ]);

	    $tickets = $model_filter->filter(Ticket::query())->paginate(30);

	    $filters = $model_filter->getFormData();

	    return view('tickets.index', compact('tickets', 'filters'));
	}

The $filters variable passed to the view allows for the form to render the previously specified filters.