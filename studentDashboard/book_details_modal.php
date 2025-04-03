<div id="bookModal" class="fixed inset-0 bg-black/30 z-50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div id="modalContent" class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="flex justify-between items-center p-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50">
            <h2 class="text-xl font-bold text-gray-800">Book Details</h2>
            <button onclick="closeModal()" class="p-2 rounded-full hover:bg-gray-200 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Content -->
        <div class="overflow-y-auto flex-1">
            <div class="flex flex-col md:flex-row">
                <!-- Book Cover Column -->
                <div class="w-full md:w-1/3 p-6 flex flex-col items-center border-r border-gray-100 bg-white">
                    <div class="w-full aspect-[2/3] flex items-center justify-center overflow-hidden rounded-lg shadow-md mb-6 bg-gray-50">
                        <img id="modalImage" alt="Book Cover" class="w-full h-full object-contain transition-transform duration-300 hover:scale-105">
                    </div>
                    
                    <div class="w-full space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-500">Status</span>
                            <span id="modalStatus" class="px-3 py-1 rounded-full text-xs font-semibold"></span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-500">Copies</span>
                            <span id="modalCopies" class="font-semibold text-blue-600"></span>
                        </div>
                        
                        <form action="" method="post" class="w-full pt-2">
                            <input type="hidden" name="book_id" id="modalBookIdReserve">
                            <button type="submit" name="reserve_book" 
                                class="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-3 rounded-lg transition-all 
                                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 shadow-md hover:shadow-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Reserve Book
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Book Details Column -->
                <div class="w-full md:w-2/3 p-6">
                    <h1 id="modalTitle" class="text-2xl md:text-3xl font-bold text-gray-900 mb-2"></h1>
                    <p id="modalAuthor" class="text-lg text-indigo-600 mb-6"></p>
                    
                    <!-- Metadata Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">ISBN</h3>
                            <p id="modalISBN" class="text-gray-800 font-medium"></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Publisher</h3>
                            <p id="modalPublisher" class="text-gray-800 font-medium"></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Published</h3>
                            <p id="modalPubYear" class="text-gray-800 font-medium"></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Edition</h3>
                            <p id="modalEdition" class="text-gray-800 font-medium"></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Genre</h3>
                            <p id="modalGenre" class="text-gray-800 font-medium"></p>
                        </div>
                    </div>
                    
                    <!-- Book Summary -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Summary</h3>
                        <div id="modalSummary" class="prose prose-indigo max-w-none text-gray-700">
                            <!-- Content will be inserted here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>