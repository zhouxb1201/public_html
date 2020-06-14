<template>
  <Layout ref="load" class="verify-order bg-f8">
    <Navbar/>
    <OrderPanelGroup v-if="detail" :items="detail" @init-list="onInitList"/>
    <Empty page-type="order" message="没有相关订单" :show-foot="false" v-else/>
  </Layout>
</template>

<script>
import Empty from "@/components/Empty";
import OrderPanelGroup from "../order/component/OrderPanelGroup";
import { GET_CODEORDER } from "@/api/verify";
export default {
  name: "verify-order",
  data() {
    return {
      detail: ""
    };
  },
  created() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      const code = $this.$route.params.code;
      GET_CODEORDER(code)
        .then(res => {
          if (res.code == 1) {
            $this.detail = res.data.order_list;
            $this.detail.order_goods = res.data.order_list.order_item_list;
            $this.$refs.load.success();
          } else {
            $this.$refs.load.result();
            $this.$Dialog
              .alert({ showCancelButton: true, message: res.data.prompt })
              .then(() => {
                $this.$store
                  .dispatch("selectStore", res.data.store_id)
                  .then(() => {
                    $this.loadData();
                  });
              })
              .catch(() => {
                $this.$router.back();
              });
          }
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    },
    onInitList({ message }) {
      const $this = this;
      $this.$Toast.success(message);
      setTimeout(() => {
        $this.loadData();
      }, 500);
    }
  },
  components: {
    OrderPanelGroup,
    Empty
  }
};
</script>
