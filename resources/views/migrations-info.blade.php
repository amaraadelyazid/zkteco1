<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Schema | Migrations Info</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .schema-card {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .schema-card:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        .badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .column-type {
            font-family: 'Courier New', monospace;
            background-color: #f3f4f6;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            font-size: 0.85rem;
        }
        .nav-pill {
            transition: all 0.2s ease;
        }
        .nav-pill:hover {
            background-color: #e5e7eb;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-indigo-600">
                    <i class="fas fa-database mr-2"></i>Database Schema
                </h1>
                <p class="text-gray-600">Overview of all database tables and relationships</p>
            </div>
            <div class="flex space-x-2">
                <a href="/migrations-info/sample-data" class="nav-pill flex items-center px-4 py-2 rounded-full bg-indigo-100 text-indigo-700 hover:bg-indigo-200">
                    <i class="fas fa-table mr-2"></i> Sample Data
                </a>
            </div>
        </div>

        <div class="grid gap-6">
            @foreach($migrations as $migration)
            <div class="schema-card bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-800">
                            <i class="fas fa-table mr-2 text-indigo-500"></i>{{ $migration['table'] }}
                        </h2>
                        <span class="badge bg-indigo-100 text-indigo-800 rounded-full px-3 py-1 text-sm font-medium">
                            {{ count($migration['columns']) }} columns
                        </span>
                    </div>
                    <p class="mt-1 text-gray-600">{{ $migration['description'] }}</p>
                </div>

                <div class="px-6 py-4">
                    <h3 class="font-medium text-gray-700 mb-3">
                        <i class="fas fa-columns mr-2 text-blue-500"></i>Columns Structure
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nullable</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Default</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comment</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($migration['columns'] as $name => $column)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">{{ $name }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="column-type">{{ $column->full_type }}</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if($column->is_nullable === 'YES')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Yes</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">No</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                                        {{ $column->column_default ?? 'NULL' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">
                                        {{ $column->column_comment ?? '-' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if(!empty($migration['relationships']))
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                    <h3 class="font-medium text-gray-700 mb-3">
                        <i class="fas fa-link mr-2 text-purple-500"></i>Relationships
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($migration['relationships'] as $rel)
                        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-gray-900">{{ $rel->COLUMN_NAME }}</h4>
                                    <p class="text-sm text-gray-500">
                                        References <span class="font-medium">{{ $rel->REFERENCED_COLUMN_NAME }}</span> on
                                        <span class="font-medium">{{ $rel->REFERENCED_TABLE_NAME }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</body>
</html>
