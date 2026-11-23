import { useState } from 'react';
import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type Option = { value: string; label: string };
type TaskData = {
    title: string; category: string; description: string; budget: string | null; budgetMin: string | null; budgetMax: string | null; budgetMode: 'single' | 'range';
    workModeValue: string; workplaceName: string | null; workplaceAddress: string | null; deadline: string | null;
};
type Props = {
    action: { action: string; method: 'post' | 'put' };
    options: { categories: Option[]; workModes: Option[]; guidelines: string[] };
    task: TaskData | null;
    resetWarning: boolean;
};

export function sanitizeTaskBudget(value: string): string {
    const filtered = value.replace(/[^0-9.]/g, '');
    const decimalPosition = filtered.indexOf('.');
    if (decimalPosition === -1) return filtered.slice(0, 6);

    const whole = filtered.slice(0, decimalPosition).slice(0, 6);
    const fraction = filtered.slice(decimalPosition + 1).replace(/\./g, '').slice(0, 2);
    return `${whole}.${fraction}`;
}

export default function TaskForm({ action, options, task, resetWarning }: Props) {
    const [workMode, setWorkMode] = useState(task?.workModeValue ?? '');
    const [budgetMode, setBudgetMode] = useState<'single' | 'range'>(task?.budgetMode ?? 'single');
    const [budget, setBudget] = useState(task?.budget ?? '');
    const [budgetMin, setBudgetMin] = useState(task?.budgetMin ?? '');
    const [budgetMax, setBudgetMax] = useState(task?.budgetMax ?? '');
    const hasWorkplace = workMode === 'on_campus' || workMode === 'off_campus';

    return (
        <Form {...action} className="space-y-5">
            {({ errors, processing }) => (
                <>
                    {resetWarning && <div role="alert" className="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">Saving these changes will send this task back for review and clear the previous review note.</div>}

                    <section className="space-y-4 rounded-xl border p-5">
                        <div><h2 className="text-lg font-semibold">Task basics</h2><p className="mt-1 text-sm text-muted-foreground">Tell students what the work is and what result you need.</p></div>
                        <div className="grid gap-2"><Label htmlFor="title">Task title</Label><Input id="title" name="title" maxLength={150} defaultValue={task?.title} required /><InputError message={errors.title} /></div>
                        <div className="grid gap-2"><Label htmlFor="category">Category</Label><select id="category" name="category" defaultValue={task?.category ? options.categories.find((option) => option.label === task.category)?.value ?? '' : ''} className="h-10 rounded-md border bg-background px-3 text-sm" required><option value="" disabled>Select a category</option>{options.categories.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select><InputError message={errors.category} /></div>
                        <div className="grid gap-2"><Label htmlFor="description">What needs to be done?</Label><Textarea id="description" name="description" maxLength={10000} defaultValue={task?.description} placeholder="Describe the work and what students should know before applying." required /><p className="text-xs text-muted-foreground">A clear description helps students decide whether to apply.</p><InputError message={errors.description} /></div>
                    </section>

                    <section className="space-y-4 rounded-xl border p-5">
                        <h2 className="text-lg font-semibold">Budget and work setup</h2>
                        <fieldset className="space-y-3"><legend className="text-sm font-medium">Estimated budget format</legend><input type="hidden" name="budget_mode" value={budgetMode} /><div className="flex flex-wrap gap-4 text-sm"><label className="flex items-center gap-2"><input type="radio" checked={budgetMode === 'single'} onChange={() => setBudgetMode('single')} />Single amount</label><label className="flex items-center gap-2"><input type="radio" checked={budgetMode === 'range'} onChange={() => setBudgetMode('range')} />Budget range</label></div><InputError message={errors.budget_mode} />{budgetMode === 'single' ? <div className="grid gap-2"><Label htmlFor="budget">Estimated amount (PHP)</Label><Input id="budget" name="budget" type="text" inputMode="decimal" maxLength={9} pattern="[0-9]{1,6}(\.[0-9]{1,2})?" value={budget} onChange={(event) => setBudget(sanitizeTaskBudget(event.target.value))} required /><InputError message={errors.budget} /></div> : <div className="grid gap-3 sm:grid-cols-2"><div className="grid gap-2"><Label htmlFor="budget_min">Minimum amount (PHP)</Label><Input id="budget_min" name="budget_min" type="text" inputMode="decimal" maxLength={9} pattern="[0-9]{1,6}(\.[0-9]{1,2})?" value={budgetMin} onChange={(event) => setBudgetMin(sanitizeTaskBudget(event.target.value))} required /><InputError message={errors.budget_min} /></div><div className="grid gap-2"><Label htmlFor="budget_max">Maximum amount (PHP)</Label><Input id="budget_max" name="budget_max" type="text" inputMode="decimal" maxLength={9} pattern="[0-9]{1,6}(\.[0-9]{1,2})?" value={budgetMax} onChange={(event) => setBudgetMax(sanitizeTaskBudget(event.target.value))} required /><InputError message={errors.budget_max} /></div><p className="text-sm text-muted-foreground sm:col-span-2">The maximum must be at least the minimum.</p></div>}</fieldset>
                        <div className="grid gap-2"><Label htmlFor="work_mode">Work mode</Label><select id="work_mode" name="work_mode" value={workMode} onChange={(event) => setWorkMode(event.target.value)} className="h-10 rounded-md border bg-background px-3 text-sm" required><option value="" disabled>Select a work mode</option>{options.workModes.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}</select><InputError message={errors.work_mode} /></div>
                        {hasWorkplace && <div className="space-y-3 rounded-lg border bg-muted/30 p-4"><p className="text-sm text-muted-foreground">Students see the place of work before applying. Enter a work location, not a home address.</p><div className="grid gap-2"><Label htmlFor="workplace_name">{workMode === 'on_campus' ? 'Campus location name' : 'Off-campus location name'}</Label><Input id="workplace_name" name="workplace_name" defaultValue={task?.workplaceName ?? ''} required /></div><div className="grid gap-2"><Label htmlFor="workplace_address">{workMode === 'on_campus' ? 'Campus location details' : 'Off-campus location details'}</Label><Input id="workplace_address" name="workplace_address" defaultValue={task?.workplaceAddress ?? ''} required /></div><InputError message={errors.workplace_name || errors.workplace_address} /></div>}
                        <div className="grid gap-2"><Label htmlFor="deadline">Deadline (optional)</Label><Input id="deadline" name="deadline" type="date" min={new Date(Date.now() + 86400000).toISOString().slice(0, 10)} defaultValue={task?.deadline ?? ''} /><InputError message={errors.deadline} /></div>
                    </section>

                    <section className="space-y-4 rounded-xl border p-5">
                        <h2 className="text-lg font-semibold">Platform rules</h2>
                        <div className="rounded-lg border bg-muted/40 p-4"><h3 className="font-medium">Task guidelines</h3><ul className="mt-2 list-disc space-y-1 pl-5 text-sm">{options.guidelines.map((guideline) => <li key={guideline}>{guideline}</li>)}</ul></div>
                        <div className="flex items-start gap-2"><input id="guidelines_confirmed" name="guidelines_confirmed" type="checkbox" value="1" required className="mt-1" /><Label htmlFor="guidelines_confirmed">I confirm that this task follows the platform rules.</Label></div><InputError message={errors.guidelines_confirmed} />
                    </section>

                    <Button type="submit" disabled={processing}>{task ? 'Save changes' : 'Submit task for review'}</Button>
                </>
            )}
        </Form>
    );
}
