@extends('layouts.app')

@section('title', 'My Profile')
@section('kicker', 'Personal Account')

@section('content')
<div class="g-page-header">
    <div>
        <h2>My Profile</h2>

        <p>
            View your personal GAgent account and system activity.
        </p>
    </div>

    <form
        method="POST"
        action="{{ route('logout') }}"
    >
        @csrf

        <button
            type="submit"
            class="g-btn"
        >
            Logout
        </button>
    </form>
</div>

<div
    style="
        display: grid;
        grid-template-columns: minmax(280px, 0.8fr) minmax(0, 1.4fr);
        gap: 20px;
    "
>
    <div class="g-card">
        <div
            style="
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 20px 0;
                text-align: center;
            "
        >
            <div
                class="g-avatar"
                style="
                    width: 82px;
                    height: 82px;
                    margin-bottom: 16px;
                    font-size: 28px;
                "
            >
                {{
                    strtoupper(
                        substr(
                            $user->name,
                            0,
                            2
                        )
                    )
                }}
            </div>

            <h3 style="margin-bottom: 5px;">
                {{ $user->name }}
            </h3>

            <p class="g-muted">
                {{ $user->email }}
            </p>

            <span class="g-badge badge-final">
                GAgent User
            </span>
        </div>
    </div>

    <div class="g-card">
        <h3>Personalized Activity</h3>

        <p class="g-muted">
            These statistics only include projects, tests,
            and reports belonging to your account.
        </p>

        <div
            style="
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 14px;
                margin-top: 20px;
            "
        >
            <div class="g-card">
                <div class="g-muted g-small">
                    My Projects
                </div>

                <div
                    style="
                        margin-top: 8px;
                        font-size: 28px;
                        font-weight: 800;
                    "
                >
                    {{ $projectCount }}
                </div>
            </div>

            <div class="g-card">
                <div class="g-muted g-small">
                    My Test Runs
                </div>

                <div
                    style="
                        margin-top: 8px;
                        font-size: 28px;
                        font-weight: 800;
                    "
                >
                    {{ $testRunCount }}
                </div>
            </div>

            <div class="g-card">
                <div class="g-muted g-small">
                    My Reports
                </div>

                <div
                    style="
                        margin-top: 8px;
                        font-size: 28px;
                        font-weight: 800;
                    "
                >
                    {{ $reportCount }}
                </div>
            </div>
        </div>

        <table
            class="g-table"
            style="margin-top: 20px;"
        >
            <tbody>
                <tr>
                    <th>Full Name</th>
                    <td>{{ $user->name }}</td>
                </tr>

                <tr>
                    <th>Email Address</th>
                    <td>{{ $user->email }}</td>
                </tr>

                <tr>
                    <th>Account Created</th>
                    <td>
                        {{
                            optional($user->created_at)
                                ->format('d M Y, H:i')
                        }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
