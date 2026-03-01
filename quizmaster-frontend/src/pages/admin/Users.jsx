import { useState, useEffect, useCallback } from 'react';
import AdminLayout from '../../components/AdminLayout';
import { adminUsersApi } from '../../lib/api';
import toast from 'react-hot-toast';
import Modal from '../../components/ui/Modal';
import Pagination from '../../components/ui/Pagination';
import Spinner from '../../components/ui/Spinner';
import { SkeletonTable } from '../../components/ui/SkeletonCard';

export default function Users() {
  const [users, setUsers] = useState([]);
  const [meta, setMeta] = useState(null);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [classFilter, setClassFilter] = useState('');
  const [generationFilter, setGenerationFilter] = useState('');
  const [classOptions, setClassOptions] = useState([]);
  const [generationOptions, setGenerationOptions] = useState([]);
  const [togglingId, setTogglingId] = useState(null);

  const [selectedUser, setSelectedUser] = useState(null);
  const [userAttempts, setUserAttempts] = useState([]);
  const [attemptsLoading, setAttemptsLoading] = useState(false);
  const [attemptsMeta, setAttemptsMeta] = useState(null);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await adminUsersApi.list({ page, search, status: statusFilter, class_name: classFilter, generation: generationFilter });
      setUsers(res.data.data);
      setMeta(res.data.meta);
    } catch {
      toast.error('Failed to load users');
    } finally {
      setLoading(false);
    }
  }, [page, search, statusFilter, classFilter, generationFilter]);

  useEffect(() => { load(); }, [load]);

  useEffect(() => {
    adminUsersApi.classOptions().then(r => setClassOptions(r.data)).catch(() => {});
    adminUsersApi.generationOptions().then(r => setGenerationOptions(r.data)).catch(() => {});
  }, []);

  const loadUserAttempts = async (user, p = 1) => {
    setSelectedUser(user);
    setAttemptsLoading(true);
    try {
      const res = await adminUsersApi.attempts(user.id, p);
      setUserAttempts(res.data.data);
      setAttemptsMeta(res.data.meta);
    } catch {
      toast.error('Failed to load attempts');
    } finally {
      setAttemptsLoading(false);
    }
  };

  const handleToggleActive = async (user) => {
    setTogglingId(user.id);
    try {
      const res = await adminUsersApi.toggleActive(user.id);
      toast.success(res.data.message);
      setUsers(prev => prev.map(u => u.id === user.id ? { ...u, is_active: res.data.is_active } : u));
    } catch {
      toast.error('Failed to toggle status');
    } finally {
      setTogglingId(null);
    }
  };

  return (
    <AdminLayout>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Users</h1>
          {meta && <p className="text-gray-500 text-sm mt-1">{meta.total} total users</p>}
        </div>

        {/* Filters */}
        <div className="card py-4">
          <div className="flex flex-wrap gap-3">
            <input
              type="text" placeholder="Search by name or email..."
              value={search} onChange={e => { setSearch(e.target.value); setPage(1); }}
              className="input flex-1 min-w-48"
            />
            <select className="input w-36" value={statusFilter} onChange={e => { setStatusFilter(e.target.value); setPage(1); }}>
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
            <select className="input w-36" value={classFilter} onChange={e => { setClassFilter(e.target.value); setPage(1); }}>
              <option value="">All Classes</option>
              {classOptions.map(c => <option key={c} value={c}>{c}</option>)}
            </select>
            <select className="input w-36" value={generationFilter} onChange={e => { setGenerationFilter(e.target.value); setPage(1); }}>
              <option value="">All Generations</option>
              {generationOptions.map(g => <option key={g} value={g}>{g}</option>)}
            </select>
          </div>
        </div>

        {/* Table */}
        <div className="card">
          {loading ? <SkeletonTable rows={8} cols={7} /> : (
            <>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-gray-200">
                      <th className="text-left py-3 px-4 font-semibold text-gray-600">#</th>
                      <th className="text-left py-3 px-4 font-semibold text-gray-600">Name</th>
                      <th className="text-left py-3 px-4 font-semibold text-gray-600">Email</th>
                      <th className="text-left py-3 px-4 font-semibold text-gray-600">Class</th>
                      <th className="text-left py-3 px-4 font-semibold text-gray-600">Generation</th>
                      <th className="text-left py-3 px-4 font-semibold text-gray-600">Attempts</th>
                      <th className="text-left py-3 px-4 font-semibold text-gray-600">Status</th>
                      <th className="text-left py-3 px-4 font-semibold text-gray-600">Joined</th>
                      <th className="text-right py-3 px-4 font-semibold text-gray-600">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {users.map((user, i) => (
                      <tr key={user.id} className="border-b border-gray-100 hover:bg-gray-50">
                        <td className="py-3 px-4 text-gray-400">{((page - 1) * 20) + i + 1}</td>
                        <td className="py-3 px-4">
                          <div className="flex items-center gap-2">
                            <div className="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center text-xs font-bold text-blue-600">
                              {user.name?.[0]?.toUpperCase()}
                            </div>
                            <span className="font-medium text-gray-800">{user.name}</span>
                          </div>
                        </td>
                        <td className="py-3 px-4 text-gray-500">{user.email}</td>
                        <td className="py-3 px-4 text-gray-500">{user.class_name || <span className="text-gray-300">—</span>}</td>
                        <td className="py-3 px-4 text-gray-500">{user.generation || <span className="text-gray-300">—</span>}</td>
                        <td className="py-3 px-4">
                          <button
                            onClick={() => loadUserAttempts(user)}
                            className="text-blue-600 hover:underline text-sm"
                          >
                            {user.quiz_attempts_count} attempts
                          </button>
                        </td>
                        <td className="py-3 px-4">
                          <span className={user.is_active ? 'badge badge-green' : 'badge badge-red'}>
                            {user.is_active ? 'Active' : 'Inactive'}
                          </span>
                        </td>
                        <td className="py-3 px-4 text-gray-500">
                          {new Date(user.created_at).toLocaleDateString()}
                        </td>
                        <td className="py-3 px-4 text-right">
                          <button
                            onClick={() => handleToggleActive(user)}
                            disabled={togglingId === user.id}
                            className={`text-xs px-3 py-1.5 rounded-lg border font-medium transition-colors ${
                              user.is_active
                                ? 'border-red-300 text-red-600 hover:bg-red-50'
                                : 'border-green-300 text-green-600 hover:bg-green-50'
                            }`}
                          >
                            {togglingId === user.id ? <Spinner size="sm" /> : user.is_active ? 'Deactivate' : 'Activate'}
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              <Pagination meta={meta} onPageChange={setPage} />
            </>
          )}
        </div>
      </div>

      {/* User Attempts Modal */}
      <Modal
        isOpen={!!selectedUser}
        onClose={() => setSelectedUser(null)}
        title={`Attempts — ${selectedUser?.name}`}
      >
        {attemptsLoading ? (
          <div className="flex justify-center py-8"><Spinner size="lg" /></div>
        ) : (
          <div className="space-y-3 max-h-96 overflow-y-auto">
            {userAttempts.length === 0 ? (
              <p className="text-center text-gray-500 py-6">No attempts found.</p>
            ) : (
              userAttempts.map(a => (
                <div key={a.id} className="flex items-center justify-between py-2 border-b">
                  <div>
                    <p className="text-xs font-mono text-gray-500">{a.attempt_code?.slice(0, 8)}…</p>
                    <p className="text-xs text-gray-400">{new Date(a.started_at).toLocaleString()}</p>
                  </div>
                  <div className="text-right">
                    <p className="font-semibold text-gray-800">{a.score}/{a.total_questions}</p>
                    <span className={a.status === 'completed' ? (a.score >= 50 ? 'badge badge-green' : 'badge badge-red') : 'badge badge-yellow'}>
                      {a.status === 'completed' ? (a.score >= 50 ? 'PASS' : 'FAIL') : 'In Progress'}
                    </span>
                  </div>
                </div>
              ))
            )}
          </div>
        )}
      </Modal>
    </AdminLayout>
  );
}
