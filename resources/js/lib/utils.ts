import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

/** shadcn-style class combiner: clsx conditions + tailwind-merge dedupe. */
export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}
