import Link from 'next/link';
import { Stethoscope } from 'lucide-react';

export default function NotFound() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 text-center">
      <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-100">
        <Stethoscope className="h-8 w-8 text-primary-600" />
      </div>
      <h1 className="mt-6 text-4xl font-bold text-gray-900">404</h1>
      <p className="mt-2 text-lg font-medium text-gray-700">Page not found</p>
      <p className="mt-1 text-sm text-gray-500">
        The page you are looking for does not exist or has been moved.
      </p>
      <Link
        href="/"
        className="mt-8 inline-flex items-center rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700"
      >
        Go to Dashboard
      </Link>
    </div>
  );
}
