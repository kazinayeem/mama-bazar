<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        table { border-collapse: collapse; width: 100%; font-family: sans-serif; }
        th { background-color: #0f4d2c; color: #ffffff; font-weight: bold; border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        td { border: 1px solid #e2e8f0; padding: 5px 8px; vertical-align: top; }
        tr:nth-child(even) { background-color: #f8fafc; }
        .success { color: #15803d; font-weight: bold; }
        .failure { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Mama Bazar — Super Admin Activity & Audit Report</h2>
    <p>Generated: {{ $generatedAt }}</p>

    <table>
        <thead>
            <tr>
                <th>UUID</th>
                <th>Date & Time</th>
                <th>Actor</th>
                <th>Actor Role</th>
                <th>Actor Type</th>
                <th>Event Name</th>
                <th>Module</th>
                <th>Target Type</th>
                <th>Target ID</th>
                <th>Status</th>
                <th>Source</th>
                <th>IP Address</th>
                <th>Location</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
                <tr>
                    <td>{{ $log->uuid }}</td>
                    <td>{{ optional($log->occurred_at)->format('Y-m-d H:i:s') }}</td>
                    <td>{{ $log->actor_name ?: 'System' }}</td>
                    <td>{{ $log->actor_role ?: '—' }}</td>
                    <td>{{ $log->actor_type }}</td>
                    <td>{{ $log->event_name }}</td>
                    <td>{{ $log->module }}</td>
                    <td>{{ $log->subject_type ?: '—' }}</td>
                    <td>{{ $log->subject_id ?: '—' }}</td>
                    <td class="{{ $log->status }}">{{ strtoupper($log->status) }}</td>
                    <td>{{ $log->source }}</td>
                    <td>{{ $log->ip_address ?: '—' }}</td>
                    <td>{{ $log->location ?: 'Location unavailable' }}</td>
                    <td>{{ $log->description ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
