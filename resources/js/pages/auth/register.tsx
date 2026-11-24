import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { SafetyNotice } from '@/components/safety-notice';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { login } from '@/routes';
import { store } from '@/routes/register';

type UserType = 'student' | 'faculty_staff' | 'organization';

type Props = {
    passwordRules: string;
};

export function sanitizeStudentId(value: string): string {
    return value.replace(/[^0-9]/g, '').slice(0, 10);
}

export default function Register({ passwordRules }: Props) {
    const [userType, setUserType] = useState<UserType>('student');
    const [idNumber, setIdNumber] = useState('');
    const affiliationLabel =
        userType === 'student' ? 'College and program' : 'College or office';
    const idLabel =
        userType === 'student' ? 'Student ID number' : 'Employee ID number';

    return (
        <>
            <Head title="Register" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="registration-form flex flex-col gap-3"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-x-4 gap-y-4 sm:grid-cols-2">
                            <div className="grid content-start gap-1.5 sm:col-span-2">
                                <Label htmlFor="user_type">
                                    Choose your account type
                                </Label>
                                <select
                                    id="user_type"
                                    name="user_type"
                                    value={userType}
                                    onChange={(event) => {
                                        setUserType(
                                            event.target.value as UserType,
                                        );
                                        setIdNumber('');
                                    }}
                                    required
                                    className="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                >
                                    <option value="student">CMU Student</option>
                                    <option value="faculty_staff">
                                        CMU Faculty / Staff
                                    </option>
                                    <option value="organization">
                                        Office or Organization Representative
                                    </option>
                                </select>
                                <InputError message={errors.user_type} />
                            </div>

                            {userType === 'organization' ? (
                                <>
                                    <div className="grid content-start gap-1.5">
                                        <Label htmlFor="organization_name">
                                            Organization name
                                        </Label>
                                        <Input
                                            id="organization_name"
                                            name="organization_name"
                                            required
                                        />
                                        <InputError
                                            message={errors.organization_name}
                                        />
                                    </div>
                                    <div className="grid content-start gap-1.5">
                                        <Label htmlFor="position">
                                            Your position
                                        </Label>
                                        <Input
                                            id="position"
                                            name="position"
                                            required
                                        />
                                        <InputError message={errors.position} />
                                    </div>
                                    <div className="grid content-start gap-1.5 sm:col-span-2">
                                        <Label htmlFor="supporting_info">
                                            About the organization and why it
                                            posts tasks
                                        </Label>
                                        <Textarea
                                            id="supporting_info"
                                            name="supporting_info"
                                            required
                                            maxLength={1000}
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Visible only to you and
                                            administrators.
                                        </p>
                                        <InputError
                                            message={errors.supporting_info}
                                        />
                                    </div>
                                </>
                            ) : (
                                <>
                                    <div className="grid content-start gap-1.5">
                                        <Label htmlFor="id_number">
                                            {idLabel}
                                        </Label>
                                        <Input
                                            id="id_number"
                                            name="id_number"
                                            required
                                            value={idNumber}
                                            onChange={(event) =>
                                                setIdNumber(
                                                    userType === 'student'
                                                        ? sanitizeStudentId(
                                                              event.target
                                                                  .value,
                                                          )
                                                        : event.target.value,
                                                )
                                            }
                                            inputMode={
                                                userType === 'student'
                                                    ? 'numeric'
                                                    : undefined
                                            }
                                            pattern={
                                                userType === 'student'
                                                    ? '[0-9]{10}'
                                                    : undefined
                                            }
                                            maxLength={
                                                userType === 'student'
                                                    ? 10
                                                    : undefined
                                            }
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            {userType === 'student'
                                                ? 'Enter exactly 10 digits.'
                                                : 'Only you and administrators can see this ID number.'}
                                        </p>
                                        <InputError
                                            message={errors.id_number}
                                        />
                                    </div>
                                    <div className="grid content-start gap-1.5">
                                        <Label htmlFor="affiliation">
                                            {affiliationLabel}
                                        </Label>
                                        <Input
                                            id="affiliation"
                                            name="affiliation"
                                            required
                                        />
                                        <InputError
                                            message={errors.affiliation}
                                        />
                                    </div>
                                </>
                            )}

                            <div className="grid content-start gap-1.5 sm:col-span-2">
                                <Label htmlFor="name">Full name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autoFocus
                                    autoComplete="name"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid content-start gap-1.5 sm:col-span-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    autoComplete="email"
                                    name="email"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid content-start gap-1.5">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    autoComplete="new-password"
                                    name="password"
                                    passwordrules={passwordRules}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid content-start gap-1.5">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    passwordrules={passwordRules}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <div className="sm:col-span-2">
                                <SafetyNotice />
                            </div>
                            <Button
                                type="submit"
                                className="w-full sm:col-span-2"
                                disabled={processing}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                Create account
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <TextLink href={login()} data-test="login-link">
                                Log in
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Create an account',
};
