import Link from "next/link";

const Topbar = () => {
    return (
        <nav className="flex">
            <Link href="/" className="p-3 hover:bg-gray-300">Home</Link>
            <Link href="/admin" className="p-3 hover:bg-gray-300">Admin</Link>
            <Link href="/staff" className="p-3 hover:bg-gray-300">Staff</Link>
        </nav>
    )
}

export default Topbar;