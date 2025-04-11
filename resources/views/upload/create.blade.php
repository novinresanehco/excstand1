<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __("Upload Excel File") }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    {{-- Session Error Display --}}
                    @if (session("error"))
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <strong class="font-bold">Error!</strong>
                            <span class="block sm:inline">{{ session("error") }}</span>
                        </div>
                    @endif

                    {{-- Validation Errors Display --}}
                    @if ($errors->any())
                        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                            <strong class="font-bold">Please fix the following errors:</strong>
                            <ul class="mt-2 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif


                    <form method="POST" action="{{ route("upload.store") }}" enctype="multipart/form-data">
                        @csrf

                        <!-- File Input -->
                        <div class="mb-4">
                            <x-input-label for="excel_file" :value="__("Excel File (.xlsx, .xls)")" />
                            <x-text-input id="excel_file" class="block mt-1 w-full"
                                          type="file" name="excel_file" required autofocus
                                          accept=".xlsx,.xls" />
                            <x-input-error :messages="$errors->get("excel_file")" class="mt-2" />
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __("Max file size: 5MB.") }}
                            </p>
                        </div>

                        <!-- Output Format -->
                        <div class="mb-4">
                            <x-input-label for="output_format" :value="__("Output Format")" />
                            <select name="output_format" id="output_format" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>
                                <option value="html" {{ old("output_format", "html") == "html" ? "selected" : "" }}>HTML</option>
                                <option value="sql" {{ old("output_format") == "sql" ? "selected" : "" }}>SQL</option>
                            </select>
                            <x-input-error :messages="$errors->get("output_format")" class="mt-2" />
                        </div>


                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button>
                                {{ __("Upload and Convert") }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>