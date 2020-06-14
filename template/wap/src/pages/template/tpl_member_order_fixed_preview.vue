<template>
  <div :class="item.id">
    <CellCardGroup
      :style="{ background:item.style.background }"
      :items="orderCardList"
      :item-icon-style="{ color:item.style.iconcolor }"
      :item-text-style="{ color:item.style.textcolor }"
      @click="toOrder"
    >
      <van-cell
        slot="head"
        class="cell"
        value-class="fs-12 text-secondary"
        is-link
        to="/order/list"
      >
        <van-icon
          v-if="item.params.iconclass"
          slot="icon"
          class="van-cell__left-icon left-icon"
          :name="item.params.iconclass"
          :style="{ color:item.style.titleiconcolor }"
        />
        <div :style="{ color:item.style.titlecolor }" slot="title">{{item.params.title}}</div>
        <div :style="{ color:item.style.titleremarkcolor }">{{item.params.remark}}</div>
      </van-cell>
    </CellCardGroup>
  </div>
</template>

<script>
import CellCardGroup from "@/components/CellCardGroup";
export default {
  name: "tpl_member_order_fixed",
  data() {
    return {};
  },
  props: {
    type: [String, Number],
    item: Object
  },
  computed: {
    orderCardList() {
      const { data } = this.item;
      const arr = [];
      for (let i in data) {
        if (data[i].is_show == "1") {
          let obj = {};
          switch (data[i].key) {
            case "unpaid":
              obj = {
                text: data[i].text,
                icon: data[i].iconclass,
                status: 0
              };
              arr.push(obj);
              break;
            case "unshipped":
              obj = {
                text: data[i].text,
                icon: data[i].iconclass,
                status: 1
              };
              arr.push(obj);
              break;
            case "unreceived":
              obj = {
                text: data[i].text,
                icon: data[i].iconclass,
                status: 2
              };
              arr.push(obj);
              break;
            case "unevaluated":
              obj = {
                text: data[i].text,
                icon: data[i].iconclass,
                status: -2
              };
              arr.push(obj);
              break;
            case "aftersale":
              obj = {
                text: data[i].text,
                icon: data[i].iconclass,
                status: -1
              };
              arr.push(obj);
              break;
          }
        }
      }
      return arr;
    }
  },
  methods: {
    toOrder({ status }) {
      this.$router.push({
        name: "order-list",
        query: {
          status
        }
      });
    }
  },
  components: {
    CellCardGroup
  }
};
</script>

<style scoped>
.cell {
  background: inherit;
}

.left-icon {
  line-height: 1.4;
  color: #323233;
}
</style>
