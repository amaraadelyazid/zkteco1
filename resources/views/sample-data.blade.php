<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Sample Data</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .data-card {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .data-card:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        .json-value {
            font-family: 'Courier New', monospace;
            background-color: #f3f4f6;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            font-size: 0.85rem;
            color: #6b7280;
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
                    <i class="fas fa-table mr-2"></i>Sample Data
                </h1>
                <p class="text-gray-600 ">Live data from your database tables</p>
            </div>
            <div class="flex space-x-2">
                <a href="/migrations-info" class="nav-pill flex items-center px-4 py-2 rounded-full bg-indigo-100 text-indigo-700 hover:bg-indigo-200">
                    <i class="fas fa-database mr-2"></i> Schema Info
                </a>
            </div>
        </div>

        <div class="space-y-8">
            @foreach($tables as $tableName => $data)
            <div class="data-card bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-800">
                            <i class="fas fa-table mr-2 text-indigo-500"></i>{{ $tableName }}
                        </h2>
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                            {{ $data->count() }} records
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    @if($data->isNotEmpty())
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                @foreach($data->first() as $key => $value)
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $key }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($data as $row)
                            <tr class="hover:bg-gray-50">
                                @foreach($row as $value)
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    @if(is_array($value) || is_object($value))
                                        <span class="json-value">{{ json_encode($value) }}</span>
                                    @elseif(is_bool($value))
                                        @if($value)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">true</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">false</span>
                                        @endif
                                    @elseif(is_null($value))
                                        <span class="text-gray-400">NULL</span>
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <div class="px-6 py-12 text-center">
                        <i class="fas fa-database text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">No data available in this table</p>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</body>
</html>
