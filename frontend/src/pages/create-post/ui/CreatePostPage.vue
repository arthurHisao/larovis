<script setup lang="ts">
import { ref, computed, onUnmounted } from "vue";
import { useRouter } from "vue-router";
import {
  ArrowLeft,
  Send,
  UploadCloud,
  X,
  AlertCircle,
  ChevronDown,
  ChevronUp,
} from "lucide-vue-next";
import { Button } from "@/shared/ui/button";
import { Card } from "@/shared/ui/card";
import { Label } from "@/shared/ui/label";
import { Textarea } from "@/shared/ui/textarea";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from "@/shared/ui/select";

interface UploadedMedia {
  id: string;
  file: File;
  previewUrl: string;
  name: string;
  sizeFormatted: string;
}

const router = useRouter();

// Modelos do formulário
const content = ref("");
const postType = ref("");
const departmentVisibility = ref("");

// Estados do Upload / Drag and Drop
const isDragging = ref(false);
const errorMessage = ref<string | null>(null);
const selectedImages = ref<UploadedMedia[]>([]);
const fileInputRef = ref<HTMLInputElement | null>(null);
const isSubmitting = ref(false);

// Controle de Expansão e Limite de Exibição
const VISIBLE_THRESHOLD = 14;
const isExpanded = ref(false);

const displayedImages = computed(() => {
  if (isExpanded.value || selectedImages.value.length <= VISIBLE_THRESHOLD) {
    return selectedImages.value;
  }
  return selectedImages.value.slice(0, VISIBLE_THRESHOLD);
});

const hiddenCount = computed(() => {
  return Math.max(0, selectedImages.value.length - VISIBLE_THRESHOLD);
});

const MAX_SIZE_MB = 10;
const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;
const ACCEPTED_TYPES = ["image/jpeg", "image/png", "image/gif", "image/webp"];

const formatFileSize = (bytes: number): string => {
  if (bytes < 1024) return `${bytes} B`;
  const kb = bytes / 1024;
  if (kb < 1024) return `${kb.toFixed(1)} KB`;
  return `${(kb / 1024).toFixed(1)} MB`;
};

const processFiles = (files: FileList | File[]) => {
  errorMessage.value = null;
  const fileArray = Array.from(files);

  for (const file of fileArray) {
    if (!ACCEPTED_TYPES.includes(file.type)) {
      errorMessage.value = `O arquivo "${file.name}" não é uma imagem suportada (JPG, PNG, GIF, WEBP).`;
      continue;
    }

    if (file.size > MAX_SIZE_BYTES) {
      errorMessage.value = `A imagem "${file.name}" ultrapassa o limite de ${MAX_SIZE_MB}MB.`;
      continue;
    }

    // Evita duplicar se o mesmo arquivo já foi adicionado
    const alreadyExists = selectedImages.value.some(
      (img) =>
        img.name === file.name &&
        img.file.size === file.size &&
        img.file.lastModified === file.lastModified,
    );
    if (alreadyExists) continue;

    const previewUrl = URL.createObjectURL(file);
    selectedImages.value.push({
      id:
        typeof crypto !== "undefined" && crypto.randomUUID
          ? crypto.randomUUID()
          : `${Date.now()}-${Math.random()}`,
      file,
      previewUrl,
      name: file.name,
      sizeFormatted: formatFileSize(file.size),
    });
  }
};

const triggerFileInput = () => {
  fileInputRef.value?.click();
};

const handleFileInputChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    processFiles(target.files);
  }
  // Reset para permitir selecionar o mesmo arquivo novamente se necessário
  target.value = "";
};

const handleDragOver = (event: DragEvent) => {
  event.preventDefault();
  event.stopPropagation();
  isDragging.value = true;
};

const handleDragLeave = (event: DragEvent) => {
  event.preventDefault();
  event.stopPropagation();
  isDragging.value = false;
};

const handleDrop = (event: DragEvent) => {
  event.preventDefault();
  event.stopPropagation();
  isDragging.value = false;
  if (event.dataTransfer?.files && event.dataTransfer.files.length > 0) {
    processFiles(event.dataTransfer.files);
  }
};

const removeImage = (id: string) => {
  const index = selectedImages.value.findIndex((img) => img.id === id);
  if (index !== -1) {
    URL.revokeObjectURL(selectedImages.value[index].previewUrl);
    selectedImages.value.splice(index, 1);
  }
};

const clearAllImages = () => {
  selectedImages.value.forEach((img) => URL.revokeObjectURL(img.previewUrl));
  selectedImages.value = [];
};

// Limpa URLs criadas para não vazar memória no navegador
onUnmounted(() => {
  clearAllImages();
});

const handleBack = () => {
  router.push({ name: "home" });
};

const handlePublish = async () => {
  isSubmitting.value = true;
  try {
    // Preparação do payload FormData pronto para o envio real via API
    const formData = new FormData();
    formData.append("content", content.value);
    formData.append("postType", postType.value);
    formData.append("departmentVisibility", departmentVisibility.value);

    selectedImages.value.forEach((img) => {
      formData.append("images[]", img.file);
    });

    console.log("Dados prontos para publicação:", {
      content: content.value,
      postType: postType.value,
      departmentVisibility: departmentVisibility.value,
      images: selectedImages.value.map((i) => i.file),
    });

    // TODO: Chamar o serviço de API aqui
  } finally {
    isSubmitting.value = false;
  }
};
</script>

<template>
  <div class="max-w-3xl mx-auto space-y-6">
    <!-- Cabeçalho da Página / Voltar -->
    <div class="flex items-center gap-4">
      <Button
        variant="ghost"
        size="sm"
        class="gap-2"
        @click="handleBack"
      >
        <ArrowLeft class="h-4 w-4" />
        Voltar para o Feed
      </Button>
    </div>

    <!-- Card Principal do Formulário -->
    <Card class="p-6 border-border bg-card shadow-sm">
      <div class="border-b border-border pb-2">
        <h1 class="text-xl font-semibold text-foreground">Nova Publicação</h1>
        <p class="text-sm text-muted-foreground mt-1">
          Compartilhe avisos, novidades ou atualizações com toda a equipe.
        </p>
      </div>

      <!-- Área de Conteúdo -->
      <div class="space-y-4 pt-4">
        <!-- Bloco de Seleção: Tipo de Publicação e Visibilidade por Departamento -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Tipo de Publicação -->
          <div class="space-y-2">
            <Label for="post-type">Tipo de publicação</Label>
            <Select v-model="postType">
              <SelectTrigger
                id="post-type"
                class="w-full"
              >
                <SelectValue placeholder="Selecione o tipo..." />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  <SelectLabel>Tipos</SelectLabel>
                  <SelectItem value="geral">Geral</SelectItem>
                  <SelectItem value="comunicado">Comunicado</SelectItem>
                  <SelectItem value="evento">Evento</SelectItem>
                </SelectGroup>
              </SelectContent>
            </Select>
          </div>

          <!-- Visibilidade por Departamento -->
          <div class="space-y-2">
            <Label for="department-visibility">
              Visibilidade do departamento
            </Label>
            <Select v-model="departmentVisibility">
              <SelectTrigger
                id="department-visibility"
                class="w-full"
              >
                <SelectValue placeholder="Selecione o departamento..." />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  <SelectLabel>Departamentos</SelectLabel>
                  <SelectItem value="todos">Todos os departamentos</SelectItem>
                  <SelectItem value="ti">Tecnologia da Informação</SelectItem>
                  <SelectItem value="rh">Recursos Humanos</SelectItem>
                </SelectGroup>
              </SelectContent>
            </Select>
          </div>
        </div>

        <!-- Textarea do Conteúdo -->
        <Textarea
          v-model="content"
          rows="5"
          placeholder="O que você gostaria de compartilhar hoje?"
          class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
        />

        <!-- Input de arquivo invisível -->
        <input
          ref="fileInputRef"
          type="file"
          multiple
          accept="image/png,image/jpeg,image/webp,image/gif"
          class="hidden"
          @change="handleFileInputChange"
        />

        <!-- Área de Upload / Dropzone -->
        <div
          :class="[
            'border-2 border-dashed rounded-lg p-6 flex flex-col items-center justify-center gap-2 text-center transition-all cursor-pointer select-none',
            isDragging
              ? 'border-primary bg-primary/10 scale-[1.01]'
              : 'border-border hover:bg-muted/30 hover:border-muted-foreground/40',
          ]"
          @click="triggerFileInput"
          @dragover="handleDragOver"
          @dragleave="handleDragLeave"
          @drop="handleDrop"
        >
          <div class="p-3 rounded-full bg-muted/60 text-muted-foreground">
            <UploadCloud class="h-6 w-6" />
          </div>
          <div class="text-xs text-muted-foreground">
            <span class="font-medium text-primary hover:underline">
              Clique para adicionar imagens
            </span>
            ou arraste arquivos aqui
          </div>
          <span class="text-[11px] text-muted-foreground/70">
            PNG, JPG, WEBP ou GIF até 10MB cada
          </span>
        </div>

        <!-- Alerta de Erro de Validação -->
        <div
          v-if="errorMessage"
          class="flex items-center gap-2 text-xs text-destructive bg-destructive/10 p-3 rounded-md border border-destructive/20"
        >
          <AlertCircle class="h-4 w-4 shrink-0" />
          <span>{{ errorMessage }}</span>
        </div>

        <!-- Previews das Imagens Selecionadas em Blocos Menores (80x80) com Tamanho e X -->
        <div
          v-if="selectedImages.length > 0"
          class="space-y-2 pt-2"
        >
          <div
            class="flex items-center justify-between text-xs text-muted-foreground"
          >
            <span class="font-medium">
              Imagens anexadas ({{ selectedImages.length }})
            </span>
            <button
              type="button"
              class="text-xs text-destructive hover:underline cursor-pointer"
              @click="clearAllImages"
            >
              Remover todas
            </button>
          </div>

          <div class="relative">
            <div class="flex flex-wrap gap-3">
              <div
                v-for="img in displayedImages"
                :key="img.id"
                class="group relative h-20 w-20 shrink-0 rounded-lg border border-border bg-card overflow-hidden shadow-xs hover:border-primary/50 transition-all"
              >
                <!-- Miniatura 80x80 -->
                <img
                  :src="img.previewUrl"
                  :alt="img.name"
                  class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                />

                <!-- Overlay inferior com a descrição do tamanho (ex: 1.2 MB) -->
                <div
                  class="absolute bottom-0 inset-x-0 bg-background/85 backdrop-blur-xs py-0.5 px-1 text-center border-t border-border/40"
                >
                  <span
                    class="text-[10px] font-medium leading-none text-muted-foreground block truncate"
                    :title="img.name"
                  >
                    {{ img.sizeFormatted }}
                  </span>
                </div>

                <!-- Botão X flutuante no topo -->
                <button
                  type="button"
                  aria-label="Remover imagem"
                  class="absolute top-1 right-1 h-5 w-5 rounded-full bg-background/90 text-foreground hover:bg-destructive hover:text-destructive-foreground shadow-xs flex items-center justify-center transition-colors cursor-pointer"
                  @click.stop="removeImage(img.id)"
                >
                  <X class="h-3 w-3" />
                </button>
              </div>
            </div>

            <!-- Camada de Névoa / Fog (degradê sutil) quando recolhido -->
            <div
              v-if="!isExpanded && selectedImages.length > VISIBLE_THRESHOLD"
              class="pointer-events-none absolute bottom-0 inset-x-0 h-14 bg-gradient-to-t from-card via-card/75 to-transparent"
            />
          </div>

          <!-- Botão discreto e acinzentado de expandir / recolher -->
          <div
            v-if="selectedImages.length > VISIBLE_THRESHOLD"
            class="flex justify-center pt-1"
          >
            <button
              type="button"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium text-muted-foreground bg-muted/50 hover:bg-muted hover:text-foreground border border-border/60 shadow-2xs transition-all cursor-pointer"
              @click="isExpanded = !isExpanded"
            >
              <component
                :is="isExpanded ? ChevronUp : ChevronDown"
                class="h-3.5 w-3.5"
              />
              <span>
                {{
                  isExpanded
                    ? "Mostrar menos"
                    : `Ver mais ${hiddenCount} imagens`
                }}
              </span>
            </button>
          </div>
        </div>
      </div>

      <!-- Rodapé / Ações -->
      <div
        class="flex items-center justify-end gap-3 pt-4 mt-6 border-t border-border"
      >
        <Button
          variant="outline"
          size="sm"
          @click="handleBack"
        >
          Cancelar
        </Button>
        <Button
          size="sm"
          class="gap-2"
          :disabled="isSubmitting"
          @click="handlePublish"
        >
          <Send class="h-4 w-4" />
          {{ isSubmitting ? "Publicando..." : "Publicar" }}
        </Button>
      </div>
    </Card>
  </div>
</template>
