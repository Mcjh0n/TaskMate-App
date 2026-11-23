import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 40 40"
            role="img"
            aria-label="TaskMate"
            xmlns="http://www.w3.org/2000/svg"
        >
            <rect
                x="2"
                y="2"
                width="36"
                height="36"
                rx="10"
                fill="currentColor"
            />
            <path
                d="M12 14h16M20 14v13m0 0-4-4m4 4 4-4"
                fill="none"
                stroke="var(--background)"
                strokeWidth="2.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
