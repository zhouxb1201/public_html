<template>
  <div :class="item.id">
    <CellPanelGroup
      :style="{ background:item.style.background }"
      :title="item.params.title"
      :title-style="{ color:item.style.titlecolor }"
      :icon="item.params.iconclass"
      :icon-style="{ color:item.style.titleiconcolor }"
      :value="item.params.remark"
      :value-style="{ color:item.style.titleremarkcolor }"
      :item-title-style="{ color:item.style.textcolor }"
      :item-text-style="{ color:item.style.highlight }"
      is-link
      to="/property"
      cols="3"
      :items="cellPanelItems"
    />
  </div>
</template>

<script>
import CellPanelGroup from "@/components/CellPanelGroup";
export default {
  name: "tpl_member_assets_fixed",
  data() {
    return {};
  },
  props: {
    type: [String, Number],
    item: Object
  },
  computed: {
    cellPanelItems() {
      const { data } = this.item;
      const arr = [];
      for (let i in data) {
        if (data[i].is_show == "1") {
          let obj = {};
          switch (data[i].key) {
            case "balance":
              obj = {
                title: data[i].text,
                text: 0,
                link: "/property/balance"
              };
              arr.push(obj);
              break;
            case "points":
              obj = {
                title: data[i].text,
                text: 0,
                link: "/property/points"
              };
              arr.push(obj);
              break;
            case "coupontype":
              obj = {
                title: data[i].text,
                text: 0,
                link: "/coupon/list"
              };
              arr.push(obj);
              break;
            case "giftvoucher":
              obj = {
                title: data[i].text,
                text: 0,
                link: "/giftvoucher/list"
              };
              arr.push(obj);
              break;
            case "store":
              obj = {
                title: data[i].text,
                text: 0,
                link: "/consumercard/list"
              };
              arr.push(obj);
              break;
            case "blockchain":
              if (config.addons.blockchain) {
                obj = {
                  title: data[i].text,
                  text: 0,
                  link: "/blockchain"
                };
                arr.push(obj);
              }
              break;
          }
        }
      }
      return arr;
    }
  },
  components: {
    CellPanelGroup
  }
};
</script>

<style scoped>
</style>
