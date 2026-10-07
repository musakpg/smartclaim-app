<!DOCTYPE html>
<html lang="en"
    x-data="{ isMobileSidebarOpen: false, isAuditingOpen: false, isAdminOpen: true, activeSubTab: 'expense_categories',
              isAddModalOpen: false,
              isEditModalOpen: false,
              editCategory: { id: '', name: '', keywords: '', description: '', is_active: 1 } }">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartClaim - Expense Categories Administration</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0b1727">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-[#f8fafc] text-[#1e293b] font-sans antialiased" :class="isMobileSidebarOpen ? 'overflow-hidden' : ''">

    <div class="flex flex-col lg:flex-row min-h-screen">

        @include('layouts.partials.manager-sidebar')

        <main class="flex-1 w-full px-4 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-6 pb-24 md:pb-8">
            <div class="space-y-6">
                <div class="border-b border-slate-200 pb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div>
                        <div>
                        <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Expense Categories</h1>
                        <p class="text-xs md:text-sm text-slate-500">Manage structure classification arrays mapped natively
                            to AI OCR parsing nodes.</p>
                    </div>
                    <div class="hidden lg:flex items-center gap-3">
                        <x-system-clock />
                    </div>
                </div>
                    <button @click="isAddModalOpen = true" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold py-2 px-4 rounded-xl shadow-sm transition-colors cursor-pointer">
                        <i class="fa-solid fa-plus mr-1"></i> Add Category
                    </button>
                </div>

                @if(session('success'))
                    <div
                        class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-semibold flex items-center gap-2.5 shadow-3xs">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div
                        class="p-4 bg-red-50 border border-red-200 rounded-2xl text-red-800 text-xs font-semibold flex items-center gap-2.5 shadow-3xs">
                        <i class="fa-solid fa-circle-exclamation text-red-500 text-base"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="p-4 bg-red-50 border border-red-200 rounded-2xl text-red-800 text-xs font-semibold flex items-center gap-2.5 shadow-3xs">
                        <i class="fa-solid fa-circle-exclamation text-red-500 text-base"></i>
                        <ul class="list-disc pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="bg-white p-4 md:p-6 rounded-3xl border border-slate-200/60 shadow-xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-xs md:text-sm font-bold text-slate-800"><i
                                class="fa-solid fa-layer-group text-blue-500 mr-1"></i> Active System Categories</h3>
                        <span
                            class="px-2.5 py-1 bg-slate-100 text-slate-700 font-bold text-[10px] md:text-[11px] rounded-lg">{{ $categories->count() }} Categories</span>
                    </div>

                    @if($categories->isEmpty())
                        <div class="py-12 flex flex-col items-center justify-center text-center">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                <i class="fa-solid fa-layer-group text-2xl text-slate-300"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-700 mb-1">No Categories Found</h3>
                            <p class="text-xs text-slate-500 max-w-sm">There are currently no expense categories defined. Click the 'Add Category' button to create your first category.</p>
                        </div>
                    @else
                    <div class="divide-y divide-slate-50 text-xs font-semibold text-slate-700 overflow-x-auto">
                        @foreach($categories as $category)
                        <div class="py-3.5 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-2 truncate">
                                @if(stripos($category->name, 'Meal') !== false)
                                    <i class="fa-solid fa-bowl-food text-slate-400 w-4"></i>
                                @elseif(stripos($category->name, 'Travel') !== false)
                                    <i class="fa-solid fa-plane text-slate-400 w-4"></i>
                                @elseif(stripos($category->name, 'Transport') !== false || stripos($category->name, 'Logistics') !== false)
                                    <i class="fa-solid fa-bus text-slate-400 w-4"></i>
                                @elseif(stripos($category->name, 'Office') !== false)
                                    <i class="fa-solid fa-paperclip text-slate-400 w-4"></i>
                                @elseif(stripos($category->name, 'Fuel') !== false || stripos($category->name, 'Automotive') !== false)
                                    <i class="fa-solid fa-gas-pump text-slate-400 w-4"></i>
                                @else
                                    <i class="fa-solid fa-box text-slate-400 w-4"></i>
                                @endif
                                <div class="flex flex-col">
                                    <span class="text-slate-800">{{ $category->name }}</span>
                                    <span class="text-[10px] text-slate-400 font-normal">Keywords: {{ $category->keywords ?? 'None' }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                @if($category->is_active)
                                    <span class="px-2.5 py-0.5 bg-emerald-50 border border-emerald-100 text-emerald-700 rounded-md font-bold text-[9px] whitespace-nowrap shrink-0">ACTIVE</span>
                                @else
                                    <span class="px-2.5 py-0.5 bg-slate-100 border border-slate-200 text-slate-500 rounded-md font-bold text-[9px] whitespace-nowrap shrink-0">INACTIVE</span>
                                @endif
                                <button @click="editCategory = { id: '{{ $category->id }}', name: '{{ addslashes($category->name) }}', keywords: '{{ addslashes($category->keywords) }}', description: '{{ addslashes($category->description) }}', is_active: {{ $category->is_active ? 1 : 0 }} }; isEditModalOpen = true;" class="text-slate-400 hover:text-blue-500 transition-colors cursor-pointer">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div
                    class="bg-[#0f172a] text-slate-300 p-4 md:p-5 rounded-3xl border border-slate-800 space-y-2 shadow-xs">
                    <h4
                        class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-1.5 text-emerald-400">
                        <i class="fa-solid fa-brain-circuit"></i> AI NLP Categorization Rules Baseline
                    </h4>
                    <p class="text-xs leading-relaxed opacity-90">Tokenized keywords parsed from Google Cloud Vision OCR
                        pass through TF-IDF arrays. Incoming items match these corporate blueprints dynamically to
                        keep company accounting workflows uncorrupted.</p>
                </div>
            </div>

            <!-- Add Category Modal -->
            <div x-show="isAddModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-slate-900/50 p-4">
                <div x-show="isAddModalOpen" x-transition.opacity class="relative w-full max-w-md bg-white rounded-3xl shadow-xl border border-slate-100" @click.away="isAddModalOpen = false">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-lg font-black text-slate-900">Add New Category</h3>
                        <button @click="isAddModalOpen = false" class="text-slate-400 hover:text-slate-600 transition-colors cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <form action="{{ route('manager.expense_categories.store') }}" method="POST" class="p-5 space-y-4" 
                          x-data="{ 
                              isSubmitting: false, 
                              newKeyword: '', 
                              keywordsList: [],
                              addKeyword() {
                                  const val = this.newKeyword.trim().replace(/,$/, '');
                                  if(val && !this.keywordsList.includes(val)) {
                                      this.keywordsList.push(val);
                                  }
                                  this.newKeyword = '';
                              },
                              handleInput() {
                                  if(this.newKeyword.includes(',')) {
                                      this.newKeyword.split(',').forEach(k => {
                                          const val = k.trim();
                                          if(val && !this.keywordsList.includes(val)) {
                                              this.keywordsList.push(val);
                                          }
                                      });
                                      this.newKeyword = '';
                                  }
                              }
                          }" 
                          @submit="isSubmitting = true">
                        @csrf
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Category Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">AI Detection Keywords <span class="text-slate-400 font-normal">(press enter or comma to add)</span></label>
                            <div class="flex flex-wrap gap-2 mb-2" x-show="keywordsList.length > 0">
                                <template x-for="(kw, i) in keywordsList" :key="i">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-blue-50 border border-blue-200 text-blue-700">
                                        <span x-text="kw"></span>
                                        <button type="button" @click="keywordsList.splice(i, 1)" class="text-blue-400 hover:text-rose-500 focus:outline-none transition-colors"><i class="fa-solid fa-xmark"></i></button>
                                    </span>
                                </template>
                            </div>
                            <input type="text" x-model="newKeyword" @keydown.enter.prevent="addKeyword()" @input="handleInput()" placeholder="Type a keyword and press Enter or comma" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all">
                            <input type="hidden" name="keywords" :value="keywordsList.join(', ')">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Description</label>
                            <textarea name="description" rows="2" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all"></textarea>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" checked id="add_active" class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500 focus:ring-2 cursor-pointer">
                            <label for="add_active" class="text-xs font-bold text-slate-700 cursor-pointer">Set as Active</label>
                        </div>
                        <div class="pt-4 flex gap-3">
                            <button type="button" @click="isAddModalOpen = false" :disabled="isSubmitting" class="flex-1 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 disabled:opacity-50 text-slate-700 text-sm font-bold rounded-xl transition-colors cursor-pointer">Cancel</button>
                            <button type="submit" :disabled="isSubmitting" class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-70 disabled:cursor-not-allowed text-white text-sm font-bold rounded-xl shadow-sm transition-colors cursor-pointer flex items-center justify-center gap-2">
                                <i x-show="isSubmitting" class="fa-solid fa-spinner fa-spin"></i>
                                <span x-text="isSubmitting ? 'Saving...' : 'Save Category'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Edit Category Modal -->
            <div x-show="isEditModalOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto overflow-x-hidden bg-slate-900/50 p-4">
                <div x-show="isEditModalOpen" x-transition.opacity class="relative w-full max-w-md bg-white rounded-3xl shadow-xl border border-slate-100" @click.away="isEditModalOpen = false">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-lg font-black text-slate-900">Edit Category</h3>
                        <button @click="isEditModalOpen = false" class="text-slate-400 hover:text-slate-600 transition-colors cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <form :action="`{{ url('manager/expense-categories') }}/${editCategory.id}`" method="POST" class="p-5 space-y-4" 
                          x-data="{ 
                              isSubmitting: false, 
                              newKeyword: '', 
                              keywordsList: [],
                              addKeyword() {
                                  const val = this.newKeyword.trim().replace(/,$/, '');
                                  if(val && !this.keywordsList.includes(val)) {
                                      this.keywordsList.push(val);
                                      this.updateParent();
                                  }
                                  this.newKeyword = '';
                              },
                              handleInput() {
                                  if(this.newKeyword.includes(',')) {
                                      this.newKeyword.split(',').forEach(k => {
                                          const val = k.trim();
                                          if(val && !this.keywordsList.includes(val)) {
                                              this.keywordsList.push(val);
                                          }
                                      });
                                      this.updateParent();
                                      this.newKeyword = '';
                                  }
                              },
                              updateParent() {
                                  editCategory.keywords = this.keywordsList.join(', ');
                              }
                          }" 
                          x-init="$watch('editCategory.keywords', val => { if(val && typeof val === 'string') keywordsList = val.split(',').map(s=>s.trim()).filter(s=>s) })" 
                          @submit="isSubmitting = true">
                        @csrf
                        @method('PUT')
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Category Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" x-model="editCategory.name" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">AI Detection Keywords <span class="text-slate-400 font-normal">(press enter or comma to add)</span></label>
                            <div class="flex flex-wrap gap-2 mb-2" x-show="keywordsList.length > 0">
                                <template x-for="(kw, i) in keywordsList" :key="i">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-blue-50 border border-blue-200 text-blue-700">
                                        <span x-text="kw"></span>
                                        <button type="button" @click="keywordsList.splice(i, 1); updateParent()" class="text-blue-400 hover:text-rose-500 focus:outline-none transition-colors"><i class="fa-solid fa-xmark"></i></button>
                                    </span>
                                </template>
                            </div>
                            <input type="text" x-model="newKeyword" @keydown.enter.prevent="addKeyword()" @input="handleInput()" placeholder="Type a keyword and press Enter or comma" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all">
                            <input type="hidden" name="keywords" :value="keywordsList.join(', ')">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Description</label>
                            <textarea name="description" x-model="editCategory.description" rows="2" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition-all"></textarea>
                        </div>
                        <div class="flex items-center gap-2 pt-2">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" x-model="editCategory.is_active" id="edit_active" class="w-4 h-4 text-blue-600 bg-slate-100 border-slate-300 rounded focus:ring-blue-500 focus:ring-2 cursor-pointer">
                            <label for="edit_active" class="text-xs font-bold text-slate-700 cursor-pointer">Set as Active</label>
                        </div>
                        <div class="pt-4 flex gap-3">
                            <button type="button" @click="isEditModalOpen = false" :disabled="isSubmitting" class="flex-1 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 disabled:opacity-50 text-slate-700 text-sm font-bold rounded-xl transition-colors cursor-pointer">Cancel</button>
                            <button type="submit" :disabled="isSubmitting" class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-70 disabled:cursor-not-allowed text-white text-sm font-bold rounded-xl shadow-sm transition-colors cursor-pointer flex items-center justify-center gap-2">
                                <i x-show="isSubmitting" class="fa-solid fa-spinner fa-spin"></i>
                                <span x-text="isSubmitting ? 'Updating...' : 'Update Category'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
        @include('layouts.partials.bottom-nav')
    </div>
</body>

</html>
