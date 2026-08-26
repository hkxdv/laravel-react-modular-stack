import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

function Hello({ name }: Readonly<{ name: string }>) {
  return <h1>Hello, {name}</h1>;
}

describe('vitest smoke test', () => {
  it('renders a react component in jsdom', () => {
    render(<Hello name="Vitest" />);

    expect(screen.getByRole('heading', { name: /hello, vitest/i })).toBeInTheDocument();
  });
});
