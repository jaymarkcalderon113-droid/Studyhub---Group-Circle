import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <img
            src="/studyhub-logo-transparent.png"
            alt="studyhub"
            className="h-96 w-96 object-contain"
        />
    );
}
