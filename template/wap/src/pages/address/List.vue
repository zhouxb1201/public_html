<template>
  <Layout ref="load" class="address-list bg-f8">
    <Navbar :isMenu="false" />
    <div class="list">
      <van-radio-group v-model="defaultId" @change="onDefault">
        <van-cell-group class="item card-group-box" v-for="(item,index) in list" :key="index">
          <van-cell class="info">
            <van-row type="flex">
              <van-col span="5" class="label">收货人</van-col>
              <van-col span="10" class="name">{{item.consigner}}</van-col>
              <van-col span="9" class="tel">{{item.mobile}}</van-col>
            </van-row>
            <van-row type="flex">
              <van-col span="5" class="label">收货地址</van-col>
              <van-col
                span="19"
                class="address"
              >{{item.province_name}}{{item.city_name}}{{item.district_name}}{{item.address}}</van-col>
            </van-row>
          </van-cell>
          <van-cell class="foot">
            <van-row type="flex" justify="space-between">
              <van-col span="8">
                <van-radio :name="item.id">设为默认</van-radio>
              </van-col>
              <van-col span="14" class="btn-group">
                <van-col span="8" class="btn e-handle" @click.native="onEdit(item.id)">
                  <van-icon name="edit" />
                  <span>编辑</span>
                </van-col>
                <van-col span="8" class="btn e-handle" @click.native="onRemove(item.id)">
                  <van-icon name="delete" />
                  <span>删除</span>
                </van-col>
              </van-col>
            </van-row>
          </van-cell>
        </van-cell-group>
      </van-radio-group>
    </div>
    <div class="fixed-foot-btn-group">
      <van-button size="normal" type="danger" round block @click="onAdd">新增地址</van-button>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import {
  GET_ADDRESSLIST,
  DEL_ADDRESS,
  SET_DEFAULTADDRESS
} from "@/api/address";
export default sfc({
  name: "address-list",
  data() {
    return {
      defaultId: "",
      list: [],
      params: {
        page_index: 1,
        page_size: 10
      }
    };
  },
  activated() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      GET_ADDRESSLIST($this.params)
        .then(({ data }) => {
          $this.list = data.address_list ? data.address_list : [];
          const obj = $this.list.filter(e => {
            return e.is_default === 1;
          })[0];
          $this.defaultId = obj ? obj.id : "";
          $this.$refs.load.success();
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    },
    onDefault(id) {
      id && SET_DEFAULTADDRESS(id);
    },
    onEdit(addressid) {
      this.$router.push({
        name: "address-post",
        query: { addressid }
      });
    },
    onRemove(id) {
      const $this = this;
      $this.$Dialog
        .confirm({
          message: "是否确定删除该地址?"
        })
        .then(() => {
          DEL_ADDRESS(id).then(res => {
            $this.$Toast.success(res.message);
            setTimeout(() => {
              $this.loadData();
            }, 500);
          });
        });
    },
    onAdd() {
      this.$router.push({
        name: "address-post"
      });
    }
  }
});
</script>

<style scoped>
.address-list >>> .list {
  margin-bottom: 80px;
}

.item {
  margin-bottom: 20px;
}

.item .info .label {
  font-weight: 800;
}

.item .info .tel {
  text-align: right;
}

.item .info .address {
  font-size: 12px;
  color: #666;
}

.item .foot .btn-group {
  display: flex;
  justify-content: flex-end;
}

.item .foot .btn {
  margin: 0 5px;
  display: flex;
  align-items: center;
  padding: 0 4px;
  justify-content: center;
}

.item .foot .btn span {
  margin-left: 5px;
}
</style>
