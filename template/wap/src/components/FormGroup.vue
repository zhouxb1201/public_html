<template>
  <div class="form-group">
    <component
      :is="'form_'+item.tag"
      v-for="(item,index) in newItems"
      :key="index"
      :item="item"
      :ref="'form_'+item.tag"
    />
  </div>
</template>
<script>
import { form } from "../pages/template";
import { isEmpty } from "@/utils/util";
import { validMobile, validCard } from "@/utils/validator";
export default {
  data() {
    return {
      newItems: []
    };
  },
  props: {
    items: {
      type: Array
    }
  },
  mounted() {
    let items = [];
    items = this.items.map(e => {
      if (e.tag == "input" || e.tag == "textarea") {
        e.value = e.value ? e.value : e.default;
      }
      return e;
    });
    this.newItems = items;
  },
  methods: {
    /**
     * 可通过$refs调用getFormData方法
     */
    getFormData() {
      const $this = this;
      let falg = true;
      let arr = [];
      for (let tag in $this.$refs) {
        if (falg) {
          $this.$refs[tag].some(({ item }) => {
            if (!$this.formValidator(item)) {
              falg = false;
              return true;
            }
            arr.push(item);
          });
        }
      }
      return !isEmpty($this.newItems) && arr.length == $this.newItems.length
        ? arr
        : false;
    },
    // 验证表单
    formValidator(item) {
      const $this = this;
      let result = true;
      const value = String(item.value || "").trim();
      if (value) {
        if (item.tag == "phone" && !validMobile(item.value)) {
          result = false;
        } else if (item.tag == "card" && !validCard(item.value)) {
          result = false;
        }
      } else {
        if (item.required) {
          result = false;
          $this.$Toast(item.label + "为必填项");
        }
      }
      return result;
    }
  },
  components: {
    ...form
  }
};
</script>
<style scoped>
</style>
