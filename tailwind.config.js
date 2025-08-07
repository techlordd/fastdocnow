import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './resources/js/**/*.vue',
        './app/Livewire/**/*.php',
        './app/Http/Controllers/**/*.php',
        './app/Models/**/*.php',
    ],
    safelist: [
        // Background colors
        'bg-gray-50', 'bg-gray-100', 'bg-gray-200', 'bg-gray-300', 'bg-gray-400', 'bg-gray-500', 'bg-gray-600', 'bg-gray-700', 'bg-gray-800', 'bg-gray-900',
        'bg-white', 'bg-black',
        'bg-primary-50', 'bg-primary-100', 'bg-primary-200', 'bg-primary-300', 'bg-primary-400', 'bg-primary-500', 'bg-primary-600', 'bg-primary-700', 'bg-primary-800', 'bg-primary-900',
        'bg-success-50', 'bg-success-100', 'bg-success-200', 'bg-success-300', 'bg-success-400', 'bg-success-500', 'bg-success-600', 'bg-success-700', 'bg-success-800', 'bg-success-900',
        'bg-red-50', 'bg-red-100', 'bg-red-200', 'bg-red-300', 'bg-red-400', 'bg-red-500', 'bg-red-600', 'bg-red-700', 'bg-red-800', 'bg-red-900',
        'bg-blue-50', 'bg-blue-100', 'bg-blue-200', 'bg-blue-300', 'bg-blue-400', 'bg-blue-500', 'bg-blue-600', 'bg-blue-700', 'bg-blue-800', 'bg-blue-900',
        'bg-green-50', 'bg-green-100', 'bg-green-200', 'bg-green-300', 'bg-green-400', 'bg-green-500', 'bg-green-600', 'bg-green-700', 'bg-green-800', 'bg-green-900',
        'bg-yellow-50', 'bg-yellow-100', 'bg-yellow-200', 'bg-yellow-300', 'bg-yellow-400', 'bg-yellow-500', 'bg-yellow-600', 'bg-yellow-700', 'bg-yellow-800', 'bg-yellow-900',
        'bg-purple-50', 'bg-purple-100', 'bg-purple-200', 'bg-purple-300', 'bg-purple-400', 'bg-purple-500', 'bg-purple-600', 'bg-purple-700', 'bg-purple-800', 'bg-purple-900',
        'bg-indigo-50', 'bg-indigo-100', 'bg-indigo-200', 'bg-indigo-300', 'bg-indigo-400', 'bg-indigo-500', 'bg-indigo-600', 'bg-indigo-700', 'bg-indigo-800', 'bg-indigo-900',
        'bg-pink-50', 'bg-pink-100', 'bg-pink-200', 'bg-pink-300', 'bg-pink-400', 'bg-pink-500', 'bg-pink-600', 'bg-pink-700', 'bg-pink-800', 'bg-pink-900',

        // Text colors
        'text-gray-50', 'text-gray-100', 'text-gray-200', 'text-gray-300', 'text-gray-400', 'text-gray-500', 'text-gray-600', 'text-gray-700', 'text-gray-800', 'text-gray-900',
        'text-white', 'text-black',
        'text-primary-50', 'text-primary-100', 'text-primary-200', 'text-primary-300', 'text-primary-400', 'text-primary-500', 'text-primary-600', 'text-primary-700', 'text-primary-800', 'text-primary-900',
        'text-success-50', 'text-success-100', 'text-success-200', 'text-success-300', 'text-success-400', 'text-success-500', 'text-success-600', 'text-success-700', 'text-success-800', 'text-success-900',
        'text-red-50', 'text-red-100', 'text-red-200', 'text-red-300', 'text-red-400', 'text-red-500', 'text-red-600', 'text-red-700', 'text-red-800', 'text-red-900',
        'text-blue-50', 'text-blue-100', 'text-blue-200', 'text-blue-300', 'text-blue-400', 'text-blue-500', 'text-blue-600', 'text-blue-700', 'text-blue-800', 'text-blue-900',
        'text-green-50', 'text-green-100', 'text-green-200', 'text-green-300', 'text-green-400', 'text-green-500', 'text-green-600', 'text-green-700', 'text-green-800', 'text-green-900',

        // Borders
        'border', 'border-2', 'border-t', 'border-b', 'border-r', 'border-l',
        'border-gray-50', 'border-gray-100', 'border-gray-200', 'border-gray-300', 'border-gray-400', 'border-gray-500', 'border-gray-600', 'border-gray-700', 'border-gray-800', 'border-gray-900',
        'border-primary-50', 'border-primary-100', 'border-primary-200', 'border-primary-300', 'border-primary-400', 'border-primary-500', 'border-primary-600', 'border-primary-700',
        'border-red-200', 'border-red-300', 'border-red-400', 'border-red-500',

        // Hover states
        'hover:bg-gray-50', 'hover:bg-gray-100', 'hover:bg-gray-200', 'hover:bg-primary-50', 'hover:bg-primary-600', 'hover:bg-primary-700',
        'hover:text-gray-600', 'hover:text-gray-800', 'hover:text-gray-900',
        'hover:border-gray-400', 'hover:border-primary-500',

        // Focus states
        'focus:border-primary-500', 'focus:ring-primary-500', 'focus:border-red-500', 'focus:ring-red-500',

        // Common utilities
        'p-1', 'p-2', 'p-3', 'p-4', 'p-6', 'p-8', 'p-12',
        'px-2', 'px-3', 'px-4', 'px-6', 'px-8',
        'py-1', 'py-2', 'py-3', 'py-4', 'py-6', 'py-8',
        'm-1', 'm-2', 'm-3', 'm-4', 'm-6', 'm-8',
        'mt-1', 'mt-2', 'mt-3', 'mt-4', 'mt-6', 'mt-8',
        'mb-1', 'mb-2', 'mb-3', 'mb-4', 'mb-6', 'mb-8',
        'ml-1', 'ml-2', 'ml-3', 'ml-4', 'ml-6', 'ml-8',
        'mr-1', 'mr-2', 'mr-3', 'mr-4', 'mr-6', 'mr-8',

        // Sizing
        'w-8', 'w-10', 'w-12', 'w-16', 'w-24', 'w-48', 'w-80', 'w-full',
        'h-8', 'h-10', 'h-12', 'h-16', 'h-24', 'h-48', 'h-screen', 'min-h-screen',

        // Flexbox
        'flex', 'flex-1', 'flex-col', 'flex-row', 'flex-shrink-0',
        'items-center', 'items-start', 'items-end', 'justify-center', 'justify-between', 'justify-start', 'justify-end',
        'space-x-1', 'space-x-2', 'space-x-3', 'space-y-2', 'space-y-4',
        'gap-2', 'gap-3', 'gap-4',

        // Border radius
        'rounded', 'rounded-lg', 'rounded-xl', 'rounded-2xl', 'rounded-full',

        // Shadows
        'shadow', 'shadow-sm', 'shadow-lg', 'shadow-xl', 'shadow-2xl',

        // Typography
        'text-xs', 'text-sm', 'text-base', 'text-lg', 'text-xl', 'text-2xl', 'text-3xl',
        'font-medium', 'font-semibold', 'font-bold',

        // Layout
        'overflow-hidden', 'overflow-y-auto',
        'transition-all', 'transition-colors', 'duration-200',
        'transform', 'hover:-translate-y-0.5',

        // Grid
        'grid', 'grid-cols-1', 'grid-cols-2', 'grid-cols-3',
        'max-w-xs', 'max-w-md', 'max-w-4xl',
        'truncate',
        'z-50',

        // Positioning
        'absolute', 'relative', 'fixed',
        'top-0', 'top-1', 'top-2', 'top-4', 'right-0', 'right-4', 'bottom-0', 'left-0', 'left-3',
        '-top-1', '-right-1', '-bottom-0.5', '-right-0.5',
        'inset-0',

        // Display
        'hidden', 'block',
        'opacity-0', 'opacity-100',

        // Animation
        'animate-spin', 'animate-bounce', 'animate-pulse',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#f0f5ff',
                    100: '#e0ebff',
                    200: '#c7d2fe',
                    300: '#a5b4fc',
                    400: '#818cf8',
                    500: '#6366f1',
                    600: '#4f46e5',
                    700: '#4338ca',
                    800: '#3730a3',
                    900: '#312e81',
                    950: '#1e1b4b',
                },
                success: {
                    50: '#f0fdf4',
                    100: '#dcfce7',
                    200: '#bbf7d0',
                    300: '#86efac',
                    400: '#4ade80',
                    500: '#22c55e',
                    600: '#16a34a',
                    700: '#15803d',
                    800: '#166534',
                    900: '#14532d',
                    950: '#052e16',
                },
            },
            animation: {
                'spin-slow': 'spin 3s linear infinite',
                'bounce-slow': 'bounce 2s infinite',
                'pulse-fast': 'pulse 1s linear infinite',
            },
        },
    },

    plugins: [
        forms,
        typography,
    ],
};
