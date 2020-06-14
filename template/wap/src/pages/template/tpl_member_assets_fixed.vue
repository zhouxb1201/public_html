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
      const { info, data } = this.item;
      const { member, config } = this.$store.state;
      const arr = [];
      for (let i in data) {
        if (data[i].is_show == "1") {
          let obj = {};
          switch (data[i].key) {
            case "balance":
              obj = {
                title: member.memberSetText.balance_style
                  ? member.memberSetText.balance_style
                  : "余额",
                text: info.balance ? info.balance : 0,
                link: "/property/balance"
              };
              arr.push(obj);
              break;
            case "points":
              obj = {
                title: member.memberSetText.point_style
                  ? member.memberSetText.point_style
                  : "积分",
                text: info.point ? info.point : 0,
                link: "/property/points"
              };
              arr.push(obj);
              break;
            case "coupontype":
              if (config.addons.coupontype) {
                obj = {
                  title: data[i].text,
                  text: info.coupon_num ? info.coupon_num : 0,
                  link: "/coupon/list"
                };
                arr.push(obj);
              }
              break;
            case "giftvoucher":
              if (config.addons.giftvoucher) {
                obj = {
                  title: data[i].text,
                  text: info.giftvoucher_num ? info.giftvoucher_num : 0,
                  link: "/giftvoucher/list"
                };
                arr.push(obj);
              }
              break;
            case "store":
              if (config.addons.store) {
                obj = {
                  title: data[i].text,
                  text: info.store_card_num ? info.store_card_num : 0,
                  link: "/consumercard/list"
                };
                arr.push(obj);
              }
              break;
            case "blockchain":
              if (config.addons.blockchain) {
                obj = {
                  title: data[i].text,
                  text: info.digital_assets ? info.digital_assets : 0,
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
