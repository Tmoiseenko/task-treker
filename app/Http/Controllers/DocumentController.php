<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\Document;
use App\Models\Task;
use App\Services\DocumentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private DocumentService $documentService
    ) {}

    /**
     * Display a listing of documents.
     */
    public function index(Request $request): View
    {
        $query = Document::with(['author', 'project']);

        // Filter by project
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Search by title and content
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('content', 'like', "%{$searchTerm}%");
            });
        }

        $documents = $query->latest()->paginate(20);

        return view('documents.index', compact('documents'));
    }

    /**
     * Show the form for creating a new document.
     */
    public function create(Request $request): View
    {
        $taskId = $request->query('task_id');
        $task = $taskId ? Task::findOrFail($taskId) : null;

        return view('documents.create', compact('task'));
    }

    /**
     * Store a newly created document in storage.
     */
    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['author_id'] = auth()->id();

        $document = $this->documentService->createDocument($data);

        // Attach to task if task_id is provided
        if ($request->filled('task_id')) {
            $task = Task::findOrFail($request->task_id);
            $this->documentService->attachToTask($document, $task);

            return redirect()
                ->route('tasks.show', $task)
                ->with('success', 'Документ успешно создан и прикреплен к задаче');
        }

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Документ успешно создан');
    }

    /**
     * Display the specified document.
     */
    public function show(Document $document): View
    {
        $document->load([
            'author',
            'project',
            'tasks',
            'versions.user'
        ]);

        return view('documents.show', compact('document'));
    }

    /**
     * Show the form for editing the specified document.
     */
    public function edit(Document $document): View
    {
        return view('documents.edit', compact('document'));
    }

    /**
     * Update the specified document in storage.
     */
    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        $this->documentService->updateDocument($document, $data);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Документ успешно обновлен');
    }

    /**
     * Remove the specified document from storage.
     */
    public function destroy(Document $document): RedirectResponse
    {
        $document->delete();

        return redirect()
            ->route('documents.index')
            ->with('success', 'Документ успешно удален');
    }

    /**
     * Attach document to a task.
     */
    public function attachToTask(Request $request, Document $document): RedirectResponse
    {
        $request->validate([
            'task_id' => ['required', 'exists:tasks,id'],
        ]);

        $task = Task::findOrFail($request->task_id);
        $this->documentService->attachToTask($document, $task);

        return redirect()
            ->back()
            ->with('success', 'Документ успешно прикреплен к задаче');
    }

    /**
     * Search documents.
     */
    public function search(Request $request): View
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $documents = $this->documentService->searchDocuments($request->q);

        return view('documents.search', compact('documents'));
    }
}
