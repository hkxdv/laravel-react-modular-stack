import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { type Table as TanstackTable, flexRender } from '@tanstack/react-table';
import { Info } from 'lucide-react';

interface RoleItem {
  id: number;
  name: string;
  permissions_count: number;
}

interface RoleTableProps {
  table: TanstackTable<RoleItem>;
}

export default function RoleTable({ table }: Readonly<RoleTableProps>) {
  const hasData = table.getRowModel().rows.length > 0;

  return (
    <div className="border-border w-full overflow-auto rounded-md border">
      <Table className="border-collapse">
        <TableHeader>
          {table.getHeaderGroups().map((headerGroup) => (
            <TableRow
              key={headerGroup.id}
              className="border-border bg-muted/40 hover:bg-muted/40 border-b"
            >
              {headerGroup.headers.map((header, index) => (
                <TableHead
                  key={header.id}
                  className={`border-r ${index === headerGroup.headers.length - 1 ? 'border-r-0' : ''} whitespace-nowrap`}
                >
                  {header.isPlaceholder
                    ? null
                    : flexRender(header.column.columnDef.header, header.getContext())}
                </TableHead>
              ))}
            </TableRow>
          ))}
        </TableHeader>
        <TableBody>
          {hasData ? (
            table.getRowModel().rows.map((row, rowIndex) => (
              <TableRow
                key={row.id}
                data-state={row.getIsSelected() && 'selected'}
                className={`${rowIndex % 2 === 0 ? 'bg-background' : 'bg-muted/40'} hover:bg-muted/50 ${
                  rowIndex < table.getRowModel().rows.length - 1 ? 'border-border border-b' : ''
                }`}
              >
                {row.getVisibleCells().map((cell, index) => (
                  <TableCell
                    key={cell.id}
                    className={`border-r ${index === row.getVisibleCells().length - 1 ? 'border-r-0' : ''}`}
                  >
                    {flexRender(cell.column.columnDef.cell, cell.getContext())}
                  </TableCell>
                ))}
              </TableRow>
            ))
          ) : (
            <TableRow>
              <TableCell colSpan={table.getAllColumns().length} className="h-24 text-center">
                <div className="flex flex-col items-center justify-center py-12 text-center">
                  <div className="bg-muted rounded-full p-3">
                    <Info className="text-muted-foreground h-6 w-6" strokeWidth={1.5} />
                  </div>
                  <h3 className="mt-4 text-lg font-medium">Sin roles</h3>
                  <p className="text-muted-foreground mt-2 max-w-xs text-sm">
                    No se encontraron roles para mostrar.
                  </p>
                </div>
              </TableCell>
            </TableRow>
          )}
        </TableBody>
      </Table>
    </div>
  );
}
