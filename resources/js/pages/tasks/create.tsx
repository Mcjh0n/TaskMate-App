import { Head } from "@inertiajs/react";
import { ArrowRight, ClipboardList, ShieldCheck } from "lucide-react";
import TaskForm from "@/components/task-form";
import { store } from "@/routes/tasks";

type Option = { value: string; label: string };
type Props = {
    options: {
        durationLimits: Record<string, number>;
        categories: Option[];
        workModes: Option[];
        guidelines: string[];
    };
    task: null;
    resetWarning: false;
};

export default function CreateTask({ options, task, resetWarning }: Props) {
    return (
        <>
            <Head title="Post a task" />
            <main className="task-editor-page mx-auto w-full max-w-6xl space-y-6 p-4 sm:p-6 lg:p-8">
                <header className="task-editor-hero">
                    <div className="task-editor-title-row">
                        <span className="task-editor-icon">
                            <ClipboardList aria-hidden="true" />
                        </span>
                        <div>
                            <p className="task-editor-eyebrow">Create a clear listing</p>
                            <h1>Post a task</h1>
                            <p className="task-editor-description">
                                Give students the details they need to decide whether the work is
                                right for them.
                            </p>
                        </div>
                    </div>
                    <div className="task-editor-flow" aria-label="Posting steps">
                        <span>
                            <b>1</b> Describe the work
                        </span>
                        <ArrowRight aria-hidden="true" />
                        <span>
                            <b>2</b> Set budget and location
                        </span>
                        <ArrowRight aria-hidden="true" />
                        <span>
                            <b>3</b> Send for review
                        </span>
                    </div>
                    <p className="task-editor-review-note">
                        <ShieldCheck aria-hidden="true" />
                        New task posts are reviewed by an administrator before students can see
                        them.
                    </p>
                </header>
                <div className="task-editor-form-wrap">
                    <TaskForm
                        action={store.form()}
                        options={options}
                        task={task}
                        resetWarning={resetWarning}
                    />
                </div>
            </main>
        </>
    );
}
