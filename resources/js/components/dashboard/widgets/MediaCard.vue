<script setup lang="ts">
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
</script>

<template>
    <Card class="bg-surface-dark/40 border-slate-800 backdrop-blur-sm flex flex-col h-full">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-4">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-rose-500">movie_filter</span>
                <CardTitle>Upcoming Media</CardTitle>
            </div>
            <Button variant="link" size="sm" class="text-xs text-primary h-auto p-0">
                View All
            </Button>
        </CardHeader>
        <CardContent>
            <div class="flex gap-4 overflow-x-auto pb-2 scrollbar-hide snap-x">
                <div class="shrink-0 w-28 group relative rounded-lg overflow-hidden snap-start cursor-pointer">
                    <img alt="Dune: Part Two Poster"
                        class="w-full h-40 object-cover transition-transform group-hover:scale-110"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuCkCZUTBCkX5kve691SU7feFZ5vMC2N74Du6OBdOzeJL5cclZMU8o5Rs2JjSjN6yuz86v7FdhjP_8m0sFUAKhSrqAR2tvkGWtrsHSxtHnbzieqGSV8brzy9ruim-Dx4WjDBhsGjMJEMuz0aFV2m6vnwOA8IvGhpQLVerxhpE1jsOMrwom5CzfwLNiiSXPwWII0Q7wGaat1RW1aEqO61MTTsax38sbBPIsBqFt_tLWG70Te3V_cmqyFm-Slwa2pTtRQ_J-eMKLGdOG00" />
                    <div
                        class="absolute inset-0 bg-linear-to-t from-black/90 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-2">
                        <span class="text-xs font-bold text-white truncate">Dune: Part Two</span>
                        <span class="text-[10px] text-slate-300">Available: Tomorrow</span>
                    </div>
                </div>
                <div class="shrink-0 w-28 group relative rounded-lg overflow-hidden snap-start cursor-pointer">
                    <img alt="Shogun Poster" class="w-full h-40 object-cover transition-transform group-hover:scale-110"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuDR30u2LCp5x1rIDNspobRGE8MHIVXTNxP0DDoLE4RkUPN5dr21GVl37YyzhowgAwswk4ovULSYWyd9J4tNMCO-u5ByzeutHfsmJAimdiUr1R_Q3bakn9A66DLB390cMxVSEuaRLOpOwGd2sJdiysamFd9ESP4KEd6YtzuiujSZj3ZkSCY6Xx0mpmbv4sgzjwA9zaKeeQMx0veqEqINq9PdQniysjYmxkyIew0WFAU2aEMzHbAbksse9FeNeK1-sBPOIHesTZ8CvJmE" />
                    <div
                        class="absolute inset-0 bg-linear-to-t from-black/90 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-2">
                        <span class="text-xs font-bold text-white truncate">Shōgun</span>
                        <span class="text-[10px] text-slate-300">S1 E6 • Tue</span>
                    </div>
                </div>
                <div class="shrink-0 w-28 group relative rounded-lg overflow-hidden snap-start cursor-pointer">
                    <img alt="No Way Up Poster"
                        class="w-full h-40 object-cover transition-transform group-hover:scale-110"
                        src="https://lh3.googleusercontent.com/aida-public/AB6AXuAdBezWqBsZLW6QpvcUFWm1C4qqNhV2eROXaKAh_ArIxxK2L5GanBPyJDI_xnvTtALepkIBmXHvyVmMJbuWQ9JvOdPIWX0nRJSXU8h7IokYG4ZhiQ-19PTMtb-vpjhQy8U7JahlCSkPt-Z_uFBi9j0jHijo5Y9f065DRBOci5WipJ6_bVQ-cn9FqSccEFy5GOpy5gBLeAqcaKBjtFqZmNyGu1glgGWwP6D3z1jk1uKNelU7BYcKP0Pe9Ew30utGhEY771nhHs2dvNFU" />
                    <div
                        class="absolute inset-0 bg-linear-to-t from-black/90 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-2">
                        <span class="text-xs font-bold text-white truncate">No Way Up</span>
                        <span class="text-[10px] text-slate-300">Downloaded</span>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
