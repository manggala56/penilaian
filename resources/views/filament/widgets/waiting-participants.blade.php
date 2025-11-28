<div class="space-y-4">
    {{-- Desktop View (Table) --}}
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">Nama Peserta</th>
                    <th scope="col" class="px-6 py-3">Kategori</th>
                    <th scope="col" class="px-6 py-3">Judul Inovasi</th>
                    <th scope="col" class="px-6 py-3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($participants as $participant)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $participant->name }}
                            <div class="text-xs text-gray-500">{{ $participant->institution }}</div>
                        </td>
                        <td class="px-6 py-4">{{ $participant->category->name }}</td>
                        <td class="px-6 py-4">{{ Str::limit($participant->innovation_title, 50) }}</td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-2">
                                @foreach($participant->assigned_judges_status as $judgeStatus)
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $judgeStatus['name'] }}</span>
                                        <span class="bg-{{ $judgeStatus['color'] }}-100 text-{{ $judgeStatus['color'] }}-800 px-2 py-0.5 rounded dark:bg-{{ $judgeStatus['color'] }}-900 dark:text-{{ $judgeStatus['color'] }}-300">
                                            {{ $judgeStatus['status'] }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td colspan="4" class="px-6 py-4 text-center">Tidak ada peserta yang menunggu penilaian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile View (Cards) --}}
    <div class="md:hidden space-y-4">
        @forelse($participants as $participant)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $participant->name }}</h3>
                        <p class="text-xs text-gray-500">{{ $participant->institution }}</p>
                    </div>
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                        {{ $participant->category->name }}
                    </span>
                </div>
                
                <div class="mb-3">
                    <p class="text-sm text-gray-600 dark:text-gray-300 italic">
                        "{{ Str::limit($participant->innovation_title, 60) }}"
                    </p>
                </div>

                <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                    <p class="text-xs font-medium text-gray-500 uppercase mb-2">Status Penilaian:</p>
                    <div class="space-y-2">
                        @foreach($participant->assigned_judges_status as $judgeStatus)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-700 dark:text-gray-300">{{ $judgeStatus['name'] }}</span>
                                <span class="bg-{{ $judgeStatus['color'] }}-100 text-{{ $judgeStatus['color'] }}-800 text-xs px-2 py-0.5 rounded dark:bg-{{ $judgeStatus['color'] }}-900 dark:text-{{ $judgeStatus['color'] }}-300">
                                    {{ $judgeStatus['status'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center p-4 text-gray-500 dark:text-gray-400">
                Tidak ada peserta yang menunggu penilaian.
            </div>
        @endforelse
    </div>
</div>
